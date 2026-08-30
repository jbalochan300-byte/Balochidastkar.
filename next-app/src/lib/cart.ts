import { cookies } from 'next/headers';
import { prisma } from '@/lib/prisma';

export type CartItem = {
  itemKey: string;
  productId: number;
  variantId: number | null;
  name: string;
  sku: string;
  quantity: number;
  color: string | null;
  imagePath: string | null;
  price: number;
  stockQuantity: number;
};

export type CheckoutItem = {
  itemKey: string;
  productId: number;
  variantId: number | null;
  name: string;
  sku: string;
  variantName: string | null;
  imagePath: string | null;
  quantity: number;
  unitPrice: number;
  subtotal: number;
  stockQuantity: number;
};

export const CART_COOKIE_NAME = 'new_website_name_cart';
export const LAST_ORDER_COOKIE_NAME = 'new_website_name_last_order';
export const CART_MAX_QUANTITY = 99;
export const CART_SHIPPING_FEE = 500;
export const CART_FREE_SHIPPING_THRESHOLD = 15000;

export function getCartItemKey(productId: number, color: string | null): string {
  return `${productId}:${(color ?? '').trim().toLowerCase()}`;
}

export async function readCart(): Promise<CartItem[]> {
  const cookieStore = await cookies();
  const raw = cookieStore.get(CART_COOKIE_NAME)?.value ?? '[]';

  try {
    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed)) return [];
    return parsed.filter((item): item is CartItem => !!item && typeof item === 'object' && typeof item.productId === 'number');
  } catch {
    return [];
  }
}

export async function writeCart(cart: CartItem[]): Promise<void> {
  const cookieStore = await cookies();
  cookieStore.set(CART_COOKIE_NAME, JSON.stringify(cart), {
    path: '/',
    sameSite: 'lax',
    httpOnly: true,
    secure: false,
  });
}

export async function clearCartCookie(): Promise<void> {
  const cookieStore = await cookies();
  cookieStore.delete(CART_COOKIE_NAME);
}

export async function setLastOrderCookie(orderId: number): Promise<void> {
  const cookieStore = await cookies();
  cookieStore.set(LAST_ORDER_COOKIE_NAME, String(orderId), {
    path: '/',
    sameSite: 'lax',
    httpOnly: true,
    secure: false,
  });
}

export async function getCartTotals() {
  const cart = await readCart();
  const subtotal = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
  const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);
  const shipping = subtotal > 0 && subtotal < CART_FREE_SHIPPING_THRESHOLD ? CART_SHIPPING_FEE : 0;

  return {
    item_count: itemCount,
    subtotal: Number(subtotal.toFixed(2)),
    shipping: Number(shipping.toFixed(2)),
    total: Number((subtotal + shipping).toFixed(2)),
  };
}

export async function addProductToCart(productId: number, quantity: number, selectedColor: string | null): Promise<boolean> {
  if (!productId || quantity < 1) return false;

  const product = await prisma.product.findFirst({
    where: { id: productId, isActive: true },
    include: {
      productImages: { orderBy: [{ sortOrder: 'asc' }, { id: 'asc' }], take: 1 },
      productVariants: { where: { isActive: true }, orderBy: [{ id: 'asc' }] },
    },
  });

  if (!product) return false;

  const normalizedColor = selectedColor ? selectedColor.trim() : null;

  let variant = null;
  if (normalizedColor) {
    variant = product.productVariants.find((item) => item.variantName.toLowerCase() === normalizedColor.toLowerCase()) ?? null;
    if (!variant && !product.colors?.split(',').map((value) => value.trim().toLowerCase()).includes(normalizedColor.toLowerCase())) {
      return false;
    }
  }

  const stockQuantity = variant ? variant.stockQuantity : product.stockQuantity;
  if (stockQuantity < 1) return false;

  const cart = await readCart();
  const itemKey = getCartItemKey(productId, normalizedColor);
  const existing = cart.find((item) => item.itemKey === itemKey);
  const nextQuantity = Math.min(quantity, CART_MAX_QUANTITY, stockQuantity);
  const finalQuantity = existing ? Math.min(existing.quantity + nextQuantity, CART_MAX_QUANTITY, stockQuantity) : nextQuantity;

  const listPrice = Number(product.price.toString());
  const salePrice = product.salePrice ? Number(product.salePrice.toString()) : null;
  const unitPrice = (salePrice && salePrice > 0 && salePrice < listPrice ? salePrice : listPrice) + (variant && variant.price ? Number(variant.price.toString()) : 0);

  const updated = [...cart.filter((item) => item.itemKey !== itemKey), {
    itemKey,
    productId: product.id,
    variantId: variant ? variant.id : null,
    name: product.name,
    sku: variant ? variant.sku : product.sku,
    quantity: finalQuantity,
    color: normalizedColor,
    imagePath: product.productImages[0]?.imagePath ?? null,
    price: unitPrice,
    stockQuantity,
  }];

  await writeCart(updated);
  return true;
}

export async function updateCartItem(itemKey: string, quantity: number): Promise<boolean> {
  const cart = await readCart();
  const existing = cart.find((item) => item.itemKey === itemKey);
  if (!existing) return false;

  if (quantity < 1) {
    const filtered = cart.filter((item) => item.itemKey !== itemKey);
    await writeCart(filtered);
    return true;
  }

  const safeQuantity = Math.min(quantity, CART_MAX_QUANTITY, existing.stockQuantity);
  const updated = cart.map((item) => item.itemKey === itemKey ? { ...item, quantity: safeQuantity } : item);
  await writeCart(updated);
  return true;
}

export async function removeCartItem(itemKey: string): Promise<boolean> {
  const cart = await readCart();
  const exists = cart.some((item) => item.itemKey === itemKey);
  if (!exists) return false;

  await writeCart(cart.filter((item) => item.itemKey !== itemKey));
  return true;
}

export async function getCheckoutItems(cart: CartItem[]): Promise<CheckoutItem[]> {
  const items: CheckoutItem[] = [];

  for (const item of cart) {
    const product = await prisma.product.findFirst({
      where: { id: item.productId, isActive: true },
      include: { productVariants: { where: { isActive: true } } },
    });

    if (!product) {
      throw new Error('A product in your cart is no longer available.');
    }

    const variant = item.variantId
      ? product.productVariants.find((entry) => entry.id === item.variantId) ?? null
      : null;

    if (item.variantId && !variant) {
      throw new Error('A selected color in your cart is no longer available.');
    }

    const regularPrice = Number(product.price.toString());
    const salePrice = product.salePrice ? Number(product.salePrice.toString()) : null;
    const basePrice = salePrice && salePrice > 0 && salePrice < regularPrice ? salePrice : regularPrice;
    const unitPrice = basePrice + (variant && variant.price ? Number(variant.price.toString()) : 0);
    const stockQuantity = variant ? variant.stockQuantity : product.stockQuantity;

    if (item.quantity > stockQuantity) {
      throw new Error(`Stock changed for ${product.name}. Please adjust your cart.`);
    }

    items.push({
      itemKey: item.itemKey,
      productId: product.id,
      variantId: variant ? variant.id : null,
      name: product.name,
      sku: variant ? variant.sku : product.sku,
      variantName: variant ? variant.variantName : item.color,
      imagePath: item.imagePath,
      quantity: item.quantity,
      unitPrice,
      subtotal: Number((unitPrice * item.quantity).toFixed(2)),
      stockQuantity,
    });
  }

  return items;
}

export function calculateCheckoutTotals(items: CheckoutItem[]) {
  const subtotal = items.reduce((sum, item) => sum + item.subtotal, 0);
  const itemCount = items.reduce((sum, item) => sum + item.quantity, 0);
  const shipping = subtotal > 0 && subtotal < CART_FREE_SHIPPING_THRESHOLD ? CART_SHIPPING_FEE : 0;

  return {
    item_count: itemCount,
    subtotal: Number(subtotal.toFixed(2)),
    shipping: Number(shipping.toFixed(2)),
    total: Number((subtotal + shipping).toFixed(2)),
  };
}
