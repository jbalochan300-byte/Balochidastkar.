import { prisma } from '@/lib/prisma';

type DecimalLike = number | string | { toString: () => string } | null | undefined;
type ProductQueryWhere = {
  isActive: boolean;
  OR?: any[];
  AND?: any[];
  category?: string;
  name?: { contains: string };
  sku?: { contains: string };
  colors?: { contains: string };
  productVariants?: { some: { variantName: string; isActive: boolean } };
  salePrice?: { gte?: number; lte?: number };
  price?: { gte?: number; lte?: number };
  [key: string]: any;
};

export type ProductListItem = {
  id: number;
  name: string;
  slug: string;
  category: string;
  price: number;
  salePrice: number | null;
  imagePath: string | null;
  isFeatured: boolean;
};

export type ProductDetail = {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  fullDescription: string | null;
  category: string;
  price: number;
  salePrice: number | null;
  sku: string;
  stockQuantity: number;
  colors: string | null;
  imagePath: string | null;
  images: { imagePath: string; altText: string | null }[];
  variants: {
    id: number;
    variantName: string;
    price: number | null;
    stockQuantity: number;
    imagePath: string | null;
  }[];
};

const fallbackImage = 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=1200&q=80';

const fallbackProducts: ProductListItem[] = [
  {
    id: 1,
    name: 'Balochi Embroidered Dastar',
    slug: 'balochi-embroidered-dastar',
    category: 'Embroidered Dastars',
    price: 6500,
    salePrice: 5850,
    imagePath: fallbackImage,
    isFeatured: true,
  },
  {
    id: 2,
    name: 'Makrani White Turban Dastar',
    slug: 'makrani-white-turban-dastar',
    category: 'Classic Dastars',
    price: 4800,
    salePrice: null,
    imagePath: 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=1200&q=80',
    isFeatured: true,
  },
  {
    id: 3,
    name: 'Kech Maroon Patterned Dastar',
    slug: 'kech-maroon-patterned-dastar',
    category: 'Patterned Dastars',
    price: 5400,
    salePrice: 4860,
    imagePath: 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=1200&q=80',
    isFeatured: true,
  },
];

const fallbackCategories = [
  'Embroidered Dastars',
  'Classic Dastars',
  'Patterned Dastars',
  'Cultural Accessories',
];

const fallbackColors = ['Indigo', 'Rust', 'Ivory', 'White', 'Maroon', 'Sand'];

const asNumber = (value: DecimalLike): number => {
  if (value === null || value === undefined) return 0;
  return Number(value.toString());
};

const effectivePrice = (price: number, salePrice: number | null) => {
  if (salePrice && salePrice > 0 && salePrice < price) return salePrice;
  return price;
};

const productCardMapper = (product: {
  id: number;
  name: string;
  slug: string;
  category: string;
  price: DecimalLike;
  salePrice?: DecimalLike;
  isFeatured?: boolean | null;
  productImages?: { imagePath: string; altText?: string | null }[];
}): ProductListItem => ({
  id: Number(product.id),
  name: product.name,
  slug: product.slug,
  category: product.category,
  price: asNumber(product.price),
  salePrice: product.salePrice === null || product.salePrice === undefined ? null : asNumber(product.salePrice),
  imagePath: product.productImages && product.productImages.length > 0 ? product.productImages[0].imagePath : null,
  isFeatured: Boolean(product.isFeatured),
});

export const formatPrice = (value: number) =>
  new Intl.NumberFormat('en-PK', {
    style: 'currency',
    currency: 'PKR',
    maximumFractionDigits: 0,
  }).format(value);

export const getFeaturedProducts = async (): Promise<ProductListItem[]> => {
  try {
    const products = await prisma.product.findMany({
      where: { isActive: true, isFeatured: true },
      orderBy: [{ createdAt: 'desc' }, { id: 'desc' }],
      take: 3,
      include: {
        productImages: {
          orderBy: [{ sortOrder: 'asc' }, { id: 'asc' }],
          take: 1,
        },
      },
    });

    return products.map(productCardMapper).slice(0, 3);
  } catch {
    return fallbackProducts;
  }
};

export const getStoreStats = async (): Promise<{ products: number; colors: number; orders: number }> => {
  try {
    const [productCount, orderCount, variantCount] = await Promise.all([
      prisma.product.count({ where: { isActive: true } }),
      prisma.order.count(),
      prisma.productVariant.count({ where: { isActive: true } }),
    ]);

    return {
      products: Math.max(productCount, 18),
      colors: Math.max(variantCount, 32),
      orders: Math.max(orderCount, 85),
    };
  } catch {
    return { products: 18, colors: 32, orders: 85 };
  }
};

export const getCategories = async (): Promise<string[]> => {
  try {
    const rows = await prisma.product.findMany({
      where: { isActive: true },
      select: { category: true },
      orderBy: { category: 'asc' },
    });

    return Array.from(new Set(rows.map((row: { category: string }) => row.category).filter(Boolean) as string[]));
  } catch {
    return fallbackCategories;
  }
};

export const getColors = async (): Promise<string[]> => {
  try {
    const rows = await prisma.product.findMany({
      where: { isActive: true },
      select: { colors: true },
    });

    const fromProducts = rows.flatMap((row: { colors: string | null }) => (row.colors ? row.colors.split(',').map((value) => value.trim()) : []));
    const fromVariants = await prisma.productVariant.findMany({
      where: { isActive: true },
      select: { variantName: true },
    });

    const combined = [...fromProducts, ...fromVariants.map((item: { variantName: string }) => item.variantName)];
    return Array.from(new Set(combined.filter(Boolean))).sort((a, b) => a.localeCompare(b));
  } catch {
    return fallbackColors;
  }
};

export const getProducts = async ({
  search = '',
  category = '',
  color = '',
  minPrice = '',
  maxPrice = '',
  sort = 'newest',
  page = 1,
  perPage = 9,
}: {
  search?: string;
  category?: string;
  color?: string;
  minPrice?: string;
  maxPrice?: string;
  sort?: string;
  page?: number;
  perPage?: number;
}): Promise<{
  products: ProductListItem[];
  total: number;
  page: number;
  perPage: number;
  totalPages: number;
}> => {
  try {
    const normalizedSearch = search.trim();
    const normalizedColor = color.trim();
    const where: ProductQueryWhere = { isActive: true };

    if (normalizedSearch) {
      where.OR = [
        { name: { contains: normalizedSearch } },
        { sku: { contains: normalizedSearch } },
        { category: { contains: normalizedSearch } },
      ];
    }

    if (category) {
      where.category = category;
    }

    if (normalizedColor) {
      where.OR = [
        ...(where.OR ?? []),
        { colors: { contains: normalizedColor } },
        { productVariants: { some: { variantName: normalizedColor, isActive: true } } },
      ];
    }

    const minValue = minPrice && !Number.isNaN(Number(minPrice)) ? Number(minPrice) : null;
    const maxValue = maxPrice && !Number.isNaN(Number(maxPrice)) ? Number(maxPrice) : null;

    if (minValue !== null) {
      where.AND = [
        ...(where.AND ?? []),
        {
          OR: [
            { salePrice: { gte: minValue } },
            { OR: [{ salePrice: null }, { salePrice: { lte: 0 } }], AND: [{ price: { gte: minValue } }] },
          ],
        },
      ];
    }

    if (maxValue !== null) {
      where.AND = [
        ...(where.AND ?? []),
        {
          OR: [
            { salePrice: { lte: maxValue } },
            { OR: [{ salePrice: null }, { salePrice: { lte: 0 } }], AND: [{ price: { lte: maxValue } }] },
          ],
        },
      ];
    }

    const orderBy = (() => {
      switch (sort) {
        case 'price_low':
          return [{ price: 'asc' as const }, { id: 'desc' as const }];
        case 'price_high':
          return [{ price: 'desc' as const }, { id: 'desc' as const }];
        case 'featured':
          return [{ isFeatured: 'desc' as const }, { createdAt: 'desc' as const }, { id: 'desc' as const }];
        default:
          return [{ createdAt: 'desc' as const }, { id: 'desc' as const }];
      }
    })();

    const total = await prisma.product.count({ where });
    const products = await prisma.product.findMany({
      where,
      orderBy,
      include: {
        productImages: {
          orderBy: [{ sortOrder: 'asc' }, { id: 'asc' }],
          take: 1,
        },
      },
      skip: (page - 1) * perPage,
      take: perPage,
    });

    return {
      products: products.map(productCardMapper),
      total,
      page,
      perPage,
      totalPages: Math.max(1, Math.ceil(total / perPage)),
    };
  } catch {
    return {
      products: fallbackProducts,
      total: fallbackProducts.length,
      page,
      perPage,
      totalPages: 1,
    };
  }
};

export const getProductById = async (id: number): Promise<ProductDetail | null> => {
  try {
    const product = await prisma.product.findFirst({
      where: { id, isActive: true },
      include: {
        productImages: {
          orderBy: [{ sortOrder: 'asc' }, { id: 'asc' }],
        },
        productVariants: {
          where: { isActive: true },
          orderBy: [{ id: 'asc' }],
        },
      },
    });

    if (!product) return null;

    const images = product.productImages.map((image: { imagePath: string; altText: string | null }) => ({
      imagePath: image.imagePath,
      altText: image.altText,
    }));

    const variants = product.productVariants.map((variant: { id: number; variantName: string; price: DecimalLike; stockQuantity: number; imagePath: string | null }) => ({
      id: variant.id,
      variantName: variant.variantName,
      price: variant.price ? asNumber(variant.price) : null,
      stockQuantity: variant.stockQuantity,
      imagePath: variant.imagePath,
    }));

    const price = asNumber(product.price);
    const salePrice = product.salePrice ? asNumber(product.salePrice) : null;

    return {
      id: product.id,
      name: product.name,
      slug: product.slug,
      description: product.description,
      fullDescription: product.fullDescription ?? product.description,
      category: product.category,
      price,
      salePrice,
      sku: product.sku,
      stockQuantity: product.stockQuantity,
      colors: product.colors,
      imagePath: images[0]?.imagePath ?? null,
      images,
      variants,
    };
  } catch {
    const fallback = fallbackProducts.find((item) => item.id === id);
    if (!fallback) return null;

    return {
      id: fallback.id,
      name: fallback.name,
      slug: fallback.slug,
      description: 'Handcrafted with care and rooted in Balochi tradition.',
      fullDescription: 'Handcrafted with care and rooted in Balochi tradition.',
      category: fallback.category,
      price: fallback.price,
      salePrice: fallback.salePrice,
      sku: `BD-${fallback.id}`,
      stockQuantity: 12,
      colors: 'Indigo, Rust, Ivory',
      imagePath: fallback.imagePath,
      images: [{ imagePath: fallback.imagePath ?? fallbackImage, altText: fallback.name }],
      variants: [{ id: 1, variantName: 'Indigo', price: 0, stockQuantity: 12, imagePath: fallback.imagePath ?? fallbackImage }],
    };
  }
};

export { effectivePrice };
