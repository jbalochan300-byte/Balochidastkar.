"use server";

import { redirect } from 'next/navigation';
import { Prisma } from '@prisma/client';
import { prisma } from '@/lib/prisma';
import { calculateCheckoutTotals, clearCartCookie, getCheckoutItems, readCart, setLastOrderCookie } from '@/lib/cart';

const normalize = (value: FormDataEntryValue | null | undefined) => (value == null ? '' : String(value).trim());

export async function submitCheckoutAction(formData: FormData) {
  const customerData = {
    full_name: normalize(formData.get('full_name')),
    email: normalize(formData.get('email')),
    phone: normalize(formData.get('phone')),
    address: normalize(formData.get('address')),
    city: normalize(formData.get('city')),
    additional_notes: normalize(formData.get('additional_notes')),
  };

  if (!customerData.full_name || customerData.full_name.length > 120) {
    redirect('/checkout?error=' + encodeURIComponent('Please enter a valid full name.'));
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerData.email) || customerData.email.length > 190) {
    redirect('/checkout?error=' + encodeURIComponent('Please enter a valid email address.'));
  }
  if (!customerData.phone || customerData.phone.length > 30) {
    redirect('/checkout?error=' + encodeURIComponent('Please enter a valid phone number.'));
  }
  if (customerData.address.length < 5) {
    redirect('/checkout?error=' + encodeURIComponent('Please enter a valid shipping address.'));
  }
  if (!customerData.city || customerData.city.length > 100) {
    redirect('/checkout?error=' + encodeURIComponent('Please enter a valid city.'));
  }

  const cart = await readCart();
  if (cart.length === 0) {
    redirect('/checkout?error=' + encodeURIComponent('Your cart is empty.'));
  }

  let checkoutItems;
  try {
    checkoutItems = await getCheckoutItems(cart);
  } catch (error) {
    redirect('/checkout?error=' + encodeURIComponent(error instanceof Error ? error.message : 'Your cart contains an unavailable item.'));
  }

  const totals = calculateCheckoutTotals(checkoutItems);
  const orderNumber = `BD-${new Date().getFullYear()}-${Date.now().toString().slice(-6)}`;

  try {
    const order = await prisma.$transaction(async (tx) => {
      const createdOrder = await tx.order.create({
        data: {
          orderNumber,
          customerName: customerData.full_name,
          customerEmail: customerData.email,
          customerPhone: customerData.phone || null,
          shippingAddress: customerData.address,
          city: customerData.city,
          additionalNotes: customerData.additional_notes || null,
          paymentMethod: 'cash_on_delivery',
          status: 'pending',
          paymentStatus: 'cod_pending',
          totalAmount: new Prisma.Decimal(totals.total.toFixed(2)),
        },
      });

      await tx.orderItem.createMany({
        data: checkoutItems.map((item) => ({
          orderId: createdOrder.id,
          productId: item.productId,
          variantId: item.variantId,
          productName: item.name,
          variantName: item.variantName,
          quantity: item.quantity,
          unitPrice: new Prisma.Decimal(item.unitPrice.toFixed(2)),
        })),
      });

      for (const item of checkoutItems) {
        if (item.variantId) {
          const variant = await tx.productVariant.findUnique({ where: { id: item.variantId } });
          if (!variant || variant.stockQuantity < item.quantity) {
            throw new Error(`Stock changed for ${item.name}. Please try again.`);
          }
          await tx.productVariant.update({
            where: { id: item.variantId },
            data: { stockQuantity: { decrement: item.quantity } },
          });
        } else {
          const product = await tx.product.findUnique({ where: { id: item.productId } });
          if (!product || product.stockQuantity < item.quantity) {
            throw new Error(`Stock changed for ${item.name}. Please try again.`);
          }
          await tx.product.update({
            where: { id: item.productId },
            data: { stockQuantity: { decrement: item.quantity } },
          });
        }
      }

      return createdOrder;
    });

    await clearCartCookie();
    await setLastOrderCookie(order.id);
    redirect('/order-success');
  } catch (error) {
    redirect('/checkout?error=' + encodeURIComponent(error instanceof Error ? error.message : 'The order could not be created. Please try again later.'));
  }
}
