"use server";

import { redirect } from 'next/navigation';
import { addProductToCart, readCart, removeCartItem, updateCartItem } from '@/lib/cart';

export async function addToCartAction(formData: FormData) {
  const productId = Number(formData.get('product_id') ?? '0');
  const quantity = Number(formData.get('quantity') ?? '1');
  const selectedColor = formData.get('color') ? String(formData.get('color')).trim() : null;
  const intent = String(formData.get('intent') ?? 'cart');

  const success = await addProductToCart(productId, quantity, selectedColor);
  if (!success) {
    const next = intent === 'buy' ? '/checkout?error=' + encodeURIComponent('The selected product is not available.') : '/cart?error=' + encodeURIComponent('The selected product is not available.');
    redirect(next);
  }

  redirect(intent === 'buy' ? '/checkout' : '/cart');
}

export async function updateCartAction(formData: FormData) {
  const itemKey = String(formData.get('item_key') ?? '');
  const quantity = Number(formData.get('quantity') ?? '1');
  await updateCartItem(itemKey, quantity);
  redirect('/cart');
}

export async function removeCartAction(formData: FormData) {
  const itemKey = String(formData.get('item_key') ?? '');
  await removeCartItem(itemKey);
  redirect('/cart');
}

export async function clearCartAction() {
  await (await import('@/lib/cart')).clearCartCookie();
  redirect('/cart');
}

export async function getCartSnapshot() {
  return await readCart();
}
