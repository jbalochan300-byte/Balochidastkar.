import { cookies } from 'next/headers';
import Link from 'next/link';
import { prisma } from '@/lib/prisma';
import { formatPrice } from '@/lib/data';

export default async function OrderSuccessPage() {
  const cookieStore = await cookies();
  const orderId = Number(cookieStore.get('new_website_name_last_order')?.value ?? '0');

  if (!orderId) {
    return (
      <main className="container py-5">
        <div className="empty-cart border text-center p-5">
          <p className="eyebrow">Order details</p>
          <h1 className="section-title h1">No recent order found.</h1>
          <p className="text-secondary">Your order confirmation may have expired.</p>
          <Link className="btn btn-dark" href="/shop">Continue shopping</Link>
        </div>
      </main>
    );
  }

  const order = await prisma.order.findUnique({
    where: { id: orderId },
    include: { orderItems: true },
  });

  if (!order) {
    return (
      <main className="container py-5">
        <div className="empty-cart border text-center p-5">
          <p className="eyebrow">Order details</p>
          <h1 className="section-title h1">No recent order found.</h1>
          <Link className="btn btn-dark" href="/shop">Continue shopping</Link>
        </div>
      </main>
    );
  }

  return (
    <main className="container py-5">
      <div className="checkout-success border p-4 p-lg-5">
        <div className="text-center mb-5">
          <p className="eyebrow">Order confirmed</p>
          <h1 className="section-title">Thank you, {order.customerName}.</h1>
          <p className="lead text-secondary mb-0">Your order has been received successfully.</p>
        </div>

        <div className="row g-4 mb-5">
          <div className="col-sm-6 col-lg-3"><span className="small text-secondary d-block">Order number</span><strong>{order.orderNumber}</strong></div>
          <div className="col-sm-6 col-lg-3"><span className="small text-secondary d-block">Payment method</span><strong>{order.paymentMethod.replace('_', ' ')}</strong></div>
          <div className="col-sm-6 col-lg-3"><span className="small text-secondary d-block">Order status</span><strong>{order.status}</strong></div>
          <div className="col-sm-6 col-lg-3"><span className="small text-secondary d-block">Total</span><strong>{formatPrice(Number(order.totalAmount.toString()))}</strong></div>
        </div>

        <h2 className="h4 mb-3">Products</h2>
        <div className="border-top">
          {order.orderItems.map((item) => (
            <div key={item.id} className="d-flex justify-content-between gap-3 border-bottom py-3">
              <span>
                {item.productName}
                {item.variantName && <small className="d-block text-secondary">Color: {item.variantName}</small>}
                <small className="d-block text-secondary">Qty: {item.quantity}</small>
              </span>
              <strong>{formatPrice(Number(item.unitPrice.toString()) * item.quantity)}</strong>
            </div>
          ))}
        </div>

        <div className="text-center mt-5">
          <Link className="btn btn-dark" href="/shop">Continue shopping</Link>
        </div>
      </div>
    </main>
  );
}
