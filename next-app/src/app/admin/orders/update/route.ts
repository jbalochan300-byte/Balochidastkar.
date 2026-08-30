import { NextResponse } from 'next/server';
import { requireAdmin } from '@/lib/admin-auth';
import { prisma } from '@/lib/prisma';

export async function POST(request: Request) {
  await requireAdmin();

  const formData = await request.formData();
  const orderId = Number(formData.get('orderId') ?? 0);
  const paymentStatus = String(formData.get('paymentStatus') ?? 'pending');
  const status = String(formData.get('status') ?? 'pending');

  if (!orderId) {
    return NextResponse.redirect(new URL('/admin/orders?error=' + encodeURIComponent('Invalid order selected.'), process.env.APP_URL || 'http://localhost:3000'));
  }

  const validPaymentStatuses = ['pending', 'paid', 'failed', 'cod_pending'];
  const validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

  if (!validPaymentStatuses.includes(paymentStatus) || !validStatuses.includes(status)) {
    return NextResponse.redirect(new URL('/admin/orders?error=' + encodeURIComponent('Please select valid payment and order statuses.'), process.env.APP_URL || 'http://localhost:3000'));
  }

  await prisma.order.update({
    where: { id: orderId },
    data: { paymentStatus, status },
  });

  return NextResponse.redirect(new URL('/admin/orders/' + orderId, process.env.APP_URL || 'http://localhost:3000'));
}
