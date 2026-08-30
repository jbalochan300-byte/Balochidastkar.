"use server";

import crypto from "node:crypto";
import { promises as fs } from "node:fs";
import path from "node:path";
import { Prisma } from "@prisma/client";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { requireAdmin } from "@/lib/admin-auth";
import { prisma } from "@/lib/prisma";

const uploadRoot = path.join(process.cwd(), "uploads", "products");
const allowedExtensions = new Set(["jpg", "jpeg", "png", "webp"]);

function slugify(value: string) {
  return value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "")
    .slice(0, 80) || "item";
}

function parseVariantMap(formData: FormData) {
  const byIndex = new Map<number, Record<string, string | number | null | File>>();

  Array.from(formData.entries()).forEach(([key, value]) => {
    const match = key.match(/^variants\[(\d+)\]\[(.+)\]$/);
    if (!match) return;
    const index = Number(match[1]);
    const field = match[2];
    if (!Number.isNaN(index)) {
      const existing = byIndex.get(index) ?? {};
      if (typeof value === "string") {
        existing[field] = value;
      } else {
        existing[field] = null;
      }
      byIndex.set(index, existing);
    }
  });

  Array.from(formData.entries()).forEach(([key, value]) => {
    const match = key.match(/^variants\[(\d+)\]\[variantImage\]$/);
    if (!match) return;
    const index = Number(match[1]);
    if (!Number.isNaN(index)) {
      const existing = byIndex.get(index) ?? {};
      if (value instanceof File) {
        existing.variantImage = value;
      }
      byIndex.set(index, existing);
    }
  });

  return Array.from(byIndex.entries())
    .sort((a, b) => a[0] - b[0])
    .map(([, data]) => data);
}

function parseDeleteImageIds(formData: FormData) {
  return formData.getAll("deleteImageIds").flatMap((value) => {
    const id = Number(value);
    return Number.isFinite(id) && id > 0 ? [id] : [];
  });
}

async function saveUpload(file: File | null) {
  if (!file || file.size === 0) return null;

  const extension = file.name.split(".").pop()?.toLowerCase() ?? "jpg";
  if (!allowedExtensions.has(extension)) {
    throw new Error("Only JPG, PNG, and WEBP images are allowed.");
  }

  await fs.mkdir(uploadRoot, { recursive: true });
  const filename = `${crypto.randomUUID()}.${extension}`;
  const destination = path.join(uploadRoot, filename);
  const buffer = Buffer.from(await file.arrayBuffer());
  await fs.writeFile(destination, buffer);
  return `uploads/products/${filename}`;
}

async function ensureUniqueSlug(base: string, fallbackSuffix = "") {
  const candidate = `${slugify(base)}${fallbackSuffix ? `-${fallbackSuffix}` : ""}`;
  let item = await prisma.product.findUnique({ where: { slug: candidate } });
  if (!item) return candidate;

  let counter = 2;
  let next = `${candidate}-${counter}`;
  while (await prisma.product.findUnique({ where: { slug: next } })) {
    counter += 1;
    next = `${candidate}-${counter}`;
  }
  return next;
}

function parseScalar(value: unknown, fallback = "") {
  if (typeof value === "string" || typeof value === "number") {
    return String(value).trim();
  }
  return fallback;
}

export async function createProductAction(formData: FormData) {
  await requireAdmin();

  const name = parseScalar(formData.get("name"));
  const sku = parseScalar(formData.get("sku")).toUpperCase();
  const category = parseScalar(formData.get("category"));
  const shortDescription = parseScalar(formData.get("shortDescription"));
  const fullDescription = parseScalar(formData.get("fullDescription"));
  const price = Number(parseScalar(formData.get("price"), "0"));
  const rawSalePrice = parseScalar(formData.get("salePrice"), "");
  const salePrice = rawSalePrice ? Number(rawSalePrice) : null;
  const stockQuantity = Number(parseScalar(formData.get("stockQuantity"), "0"));
  const status = parseScalar(formData.get("status"), "active") === "active" ? true : false;
  const isFeatured = formData.get("isFeatured") === "on" || formData.get("isFeatured") === "1";
  const mainImage = formData.get("mainImage");
  const galleryFiles = formData.getAll("galleryImages").filter((item): item is File => item instanceof File && item.size > 0);

  if (!name || !sku || !category || !shortDescription || !fullDescription) {
    redirect("/admin/products/new?error=" + encodeURIComponent("All product fields are required."));
  }

  if (!Number.isFinite(price) || price < 0) {
    redirect("/admin/products/new?error=" + encodeURIComponent("Regular price must be a valid non-negative number."));
  }

  if (salePrice !== null && (!Number.isFinite(salePrice) || salePrice < 0 || salePrice >= price)) {
    redirect("/admin/products/new?error=" + encodeURIComponent("Sale price must be lower than the regular price."));
  }

  const existingSku = await prisma.product.findUnique({ where: { sku } });
  if (existingSku) {
    redirect("/admin/products/new?error=" + encodeURIComponent("A product with this SKU already exists."));
  }

  const baseSlug = await ensureUniqueSlug(`${name}-${sku}`);
  const productData = {
    name,
    slug: baseSlug,
    sku,
    category,
    shortDescription,
    fullDescription,
    description: fullDescription,
    price: new Prisma.Decimal(price.toFixed(2)),
    salePrice: salePrice !== null ? new Prisma.Decimal(salePrice.toFixed(2)) : null,
    stockQuantity: Number.isFinite(stockQuantity) ? Math.max(0, Math.floor(stockQuantity)) : 0,
    isFeatured,
    isActive: status,
    colors: "",
  };

  const product = await prisma.product.create({ data: productData });

  const productImageRecords: { imagePath: string; altText: string; sortOrder: number }[] = [];
  const mainSaved = await saveUpload(mainImage instanceof File ? mainImage : null);
  if (mainSaved) {
    productImageRecords.push({ imagePath: mainSaved, altText: name, sortOrder: 0 });
  }

  for (let index = 0; index < galleryFiles.length; index += 1) {
    const saved = await saveUpload(galleryFiles[index]);
    if (saved) {
      productImageRecords.push({ imagePath: saved, altText: `${name} gallery ${index + 1}`, sortOrder: productImageRecords.length + 1 });
    }
  }

  if (productImageRecords.length > 0) {
    await prisma.productImage.createMany({
      data: productImageRecords.map((image) => ({
        productId: product.id,
        imagePath: image.imagePath,
        altText: image.altText,
        sortOrder: image.sortOrder,
      })),
    });
  }

  const variantEntries = parseVariantMap(formData);
  const variantNames: string[] = [];

  for (const item of variantEntries) {
    const variantName = parseScalar(item.variantName ?? "").trim();
    if (!variantName) continue;

    const variantPrice = item.additionalPrice ? Number(String(item.additionalPrice)) : 0;
    const variantStock = Number(String(item.stockQuantity ?? "0"));
    const variantStatus = parseScalar(item.status ?? "active") === "active" ? true : false;
    const variantImage = item.variantImage instanceof File && item.variantImage.size > 0 ? item.variantImage : null;
    const variantImagePath = variantImage ? await saveUpload(variantImage) : null;
    const variantSku = `${sku}-${slugify(variantName)}`.slice(0, 100);

    variantNames.push(variantName);
    await prisma.productVariant.create({
      data: {
        productId: product.id,
        variantName,
        sku: variantSku,
        price: variantPrice > 0 ? new Prisma.Decimal(variantPrice.toFixed(2)) : null,
        stockQuantity: Number.isFinite(variantStock) ? Math.max(0, Math.floor(variantStock)) : 0,
        imagePath: variantImagePath,
        isActive: variantStatus,
      },
    });
  }

  if (variantNames.length > 0) {
    await prisma.product.update({
      where: { id: product.id },
      data: { colors: variantNames.join(", ") },
    });
  }

  revalidatePath("/admin/products");
  redirect("/admin/products");
}

export async function updateProductAction(formData: FormData) {
  await requireAdmin();

  const productId = Number(formData.get("productId") ?? 0);
  if (!productId) {
    redirect("/admin/products?error=" + encodeURIComponent("Product not found."));
  }

  const product = await prisma.product.findUnique({ where: { id: productId } });
  if (!product) {
    redirect("/admin/products?error=" + encodeURIComponent("Product not found."));
  }

  const name = parseScalar(formData.get("name"));
  const sku = parseScalar(formData.get("sku")).toUpperCase();
  const category = parseScalar(formData.get("category"));
  const shortDescription = parseScalar(formData.get("shortDescription"));
  const fullDescription = parseScalar(formData.get("fullDescription"));
  const price = Number(parseScalar(formData.get("price"), "0"));
  const rawSalePrice = parseScalar(formData.get("salePrice"), "");
  const salePrice = rawSalePrice ? Number(rawSalePrice) : null;
  const stockQuantity = Number(parseScalar(formData.get("stockQuantity"), "0"));
  const isFeatured = formData.get("isFeatured") === "on" || formData.get("isFeatured") === "1";
  const status = parseScalar(formData.get("status"), "active") === "active" ? true : false;

  if (!name || !sku || !category || !shortDescription || !fullDescription) {
    redirect("/admin/products/" + productId + "/edit?error=" + encodeURIComponent("All product fields are required."));
  }

  if (!Number.isFinite(price) || price < 0) {
    redirect("/admin/products/" + productId + "/edit?error=" + encodeURIComponent("Regular price must be a valid non-negative number."));
  }

  if (salePrice !== null && (!Number.isFinite(salePrice) || salePrice < 0 || salePrice >= price)) {
    redirect("/admin/products/" + productId + "/edit?error=" + encodeURIComponent("Sale price must be lower than the regular price."));
  }

  const duplicateSku = await prisma.product.findFirst({
    where: {
      sku,
      NOT: { id: productId },
    },
  });

  if (duplicateSku) {
    redirect("/admin/products/" + productId + "/edit?error=" + encodeURIComponent("A product with this SKU already exists."));
  }

  const nextSlug = await ensureUniqueSlug(`${name}-${sku}`);
  const updatedProduct = await prisma.product.update({
    where: { id: productId },
    data: {
      name,
      slug: nextSlug,
      sku,
      category,
      shortDescription,
      fullDescription,
      description: fullDescription,
      price: new Prisma.Decimal(price.toFixed(2)),
      salePrice: salePrice !== null ? new Prisma.Decimal(salePrice.toFixed(2)) : null,
      stockQuantity: Number.isFinite(stockQuantity) ? Math.max(0, Math.floor(stockQuantity)) : 0,
      isFeatured,
      isActive: status,
    },
  });

  const deleteIds = parseDeleteImageIds(formData);
  if (deleteIds.length > 0) {
    await prisma.productImage.deleteMany({
      where: { id: { in: deleteIds }, productId },
    });
  }

  const newMainImage = formData.get("mainImage");
  const mainSaved = newMainImage instanceof File && newMainImage.size > 0 ? await saveUpload(newMainImage) : null;
  if (mainSaved) {
    await prisma.productImage.create({
      data: {
        productId: updatedProduct.id,
        imagePath: mainSaved,
        altText: name,
        sortOrder: 0,
      },
    });
  }

  const galleryFiles = formData.getAll("galleryImages").filter((item): item is File => item instanceof File && item.size > 0);
  for (let index = 0; index < galleryFiles.length; index += 1) {
    const saved = await saveUpload(galleryFiles[index]);
    if (saved) {
      await prisma.productImage.create({
        data: {
          productId: updatedProduct.id,
          imagePath: saved,
          altText: `${name} gallery ${index + 1}`,
          sortOrder: 999 + index,
        },
      });
    }
  }

  const variantEntries = parseVariantMap(formData);
  const currentVariants = await prisma.productVariant.findMany({ where: { productId } });
  const variantNames: string[] = [];

  for (const variant of currentVariants) {
    const matchingEntry = variantEntries.find((entry) => Number(entry.id ?? 0) === variant.id);
    if (!matchingEntry) {
      await prisma.productVariant.delete({ where: { id: variant.id } });
    }
  }

  for (const item of variantEntries) {
    const variantName = parseScalar(item.variantName ?? "").trim();
    if (!variantName) continue;

    const variantId = Number(item.id ?? 0);
    const variantPrice = item.additionalPrice ? Number(String(item.additionalPrice)) : 0;
    const variantStock = Number(String(item.stockQuantity ?? "0"));
    const variantStatus = parseScalar(item.status ?? "active") === "active" ? true : false;
    const variantImage = item.variantImage instanceof File && item.variantImage.size > 0 ? item.variantImage : null;
    const variantImagePath = variantImage ? await saveUpload(variantImage) : null;
    const variantSku = `${sku}-${slugify(variantName)}`.slice(0, 100);

    variantNames.push(variantName);

    if (variantId > 0) {
      await prisma.productVariant.update({
        where: { id: variantId },
        data: {
          variantName,
          sku: variantSku,
          price: variantPrice > 0 ? new Prisma.Decimal(variantPrice.toFixed(2)) : null,
          stockQuantity: Number.isFinite(variantStock) ? Math.max(0, Math.floor(variantStock)) : 0,
          imagePath: variantImagePath ?? undefined,
          isActive: variantStatus,
        },
      });
    } else {
      await prisma.productVariant.create({
        data: {
          productId: updatedProduct.id,
          variantName,
          sku: variantSku,
          price: variantPrice > 0 ? new Prisma.Decimal(variantPrice.toFixed(2)) : null,
          stockQuantity: Number.isFinite(variantStock) ? Math.max(0, Math.floor(variantStock)) : 0,
          imagePath: variantImagePath,
          isActive: variantStatus,
        },
      });
    }
  }

  if (variantNames.length > 0) {
    await prisma.product.update({
      where: { id: productId },
      data: { colors: variantNames.join(", ") },
    });
  }

  revalidatePath("/admin/products");
  revalidatePath("/admin/products/[id]/edit");
  redirect("/admin/products");
}

export async function deleteProductAction(formData: FormData) {
  await requireAdmin();
  const productId = Number(formData.get("productId") ?? 0);
  if (!productId) {
    redirect("/admin/products?error=" + encodeURIComponent("Invalid product selected."));
  }

  const product = await prisma.product.findUnique({
    where: { id: productId },
    include: { productImages: true, productVariants: { include: { orderItems: false } } },
  });

  if (product) {
    // Collect all image paths before deleting
    const imagePaths = product.productImages.map((img) => img.imagePath);
    product.productVariants.forEach((variant) => {
      if (variant.imagePath) imagePaths.push(variant.imagePath);
    });

    // Delete product and cascade deletes variants and images
    await prisma.product.delete({ where: { id: product.id } });

    // Clean up uploaded files
    const uploadRoot = path.join(process.cwd(), "uploads", "products");
    for (const imagePath of imagePaths) {
      if (!imagePath) continue;
      try {
        const candidate = path.resolve(path.join(process.cwd(), imagePath));
        const uploadRootResolved = path.resolve(uploadRoot);
        // Security check: ensure file is within uploads directory
        if (candidate.startsWith(uploadRootResolved + path.sep)) {
          await fs.unlink(candidate);
        }
      } catch {
        // File already deleted or doesn't exist, continue
      }
    }
  }

  revalidatePath("/admin/products");
  redirect("/admin/products");
}
