import Link from 'next/link';
import { notFound } from 'next/navigation';
import { requireAdmin } from '@/lib/admin-auth';
import { prisma } from '@/lib/prisma';

function paymentStatusClass(status: string) {
  switch (status) {
    case 'paid': return 'text-bg-success';
    case 'failed': return 'text-bg-danger';
    case 'cod_pending': return 'text-bg-info';
    default: return 'text-bg-warning';
  }
}

function orderStatusClass(status: string) {
  switch (status) {
    case 'delivered': return 'text-bg-success';
    case 'cancelled': return 'text-bg-danger';
    case 'pending':
    case 'processing': return 'text-bg-warning';
    default: return 'text-bg-secondary';
  }
}

export default async function AdminOrderDetailsPage({ params }: { params: { id: string } }) {
  await requireAdmin();

  const orderId = Number(params.id);
  if (!orderId) notFound();

  const order = await prisma.order.findUnique({
    where: { id: orderId },
    include: { orderItems: true },
  });

  if (!order) notFound();

  return (
    <div className="container-fluid p-4 p-lg-5">
      <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
          <p className="eyebrow mb-2">Order review</p>
          <h1 className="section-title mb-2">Order details</h1>
          <p className="text-secondary mb-0">Inspect the customer purchase and update its progress.</p>
        </div>
        <Link className="btn btn-outline-secondary" href="/admin/orders">Back to orders</Link>
      </div>

      <div className="row g-4">
        <div className="col-lg-8">
          <section className="bg-white border rounded p-4 mb-4">
            <div className="d-flex flex-column flex-sm-row justify-content-between gap-3 mb-4">
              <div>
                <p className="eyebrow mb-1">Order number</p>
                <h2 className="h3 mb-0">{order.orderNumber}</h2>
              </div>
              <div className="text-sm-end">
                <span className={`badge ${orderStatusClass(order.status)} me-2`}>{order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span>
                <span className={`badge ${paymentStatusClass(order.paymentStatus)}`}>{order.paymentStatus.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())}</span>
              </div>
            </div>

            <h3 className="h5">Products</h3>
            <div className="table-responsive">
              <table className="table align-middle">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Color</th>
                    <th>Quantity</th>
                    <th>Unit price</th>
                    <th className="text-end">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  {order.orderItems.map((item) => (
                    <tr key={item.id}>
                      <td>{item.productName}</td>
                      <td>{item.variantName || '—'}</td>
                      <td>{item.quantity}</td>
                      <td>{new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', maximumFractionDigits: 0 }).format(Number(item.unitPrice.toString()))}</td>
                      <td className="text-end">{new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', maximumFractionDigits: 0 }).format(Number(item.unitPrice.toString()) * item.quantity)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="d-flex justify-content-end"><strong>Total: {new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', maximumFractionDigits: 0 }).format(Number(order.totalAmount.toString()))}</strong></div>
          </section>

          <section className="bg-white border rounded p-4">
            <h2 className="h5 mb-3">Order status</h2>
            <form action="/admin/orders/update" method="post">
              <input type="hidden" name="orderId" value={order.id} />
              <div className="row g-3">
                <div className="col-md-6">
                  <label className="form-label" htmlFor="payment_status">Payment</label>
                  <select className="form-select" id="payment_status" name="paymentStatus" defaultValue={order.paymentStatus}>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="failed">Failed</option>
                    <option value="cod_pending">Cash on delivery pending</option>
                  </select>
                </div>
                <div className="col-md-6">
                  <label className="form-label" htmlFor="status">Order status</label>
                  <select className="form-select" id="status" name="status" defaultValue={order.status}>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                </div>
              </div>
              <div className="d-flex justify-content-end mt-4">
                <button type="submit" className="btn btn-dark">Update status</button>
              </div>
            </form>
          </section>
        </div>

        <aside className="col-lg-4">
          <section className="bg-white border rounded p-4 mb-4">
            <h2 className="h5 mb-3">Customer</h2>
            <p className="mb-1"><strong>{order.customerName}</strong></p>
            <p className="small text-secondary mb-1">{order.customerEmail}</p>
            <p className="small text-secondary mb-0">{order.customerPhone || 'No phone provided'}</p>
          </section>

          <section className="bg-white border rounded p-4">
            <h2 className="h5 mb-3">Shipping</h2>
            <p className="mb-1"><strong>City:</strong> {order.city}</p>
            <p className="mb-1"><strong>Address:</strong></p>
            <p className="mb-0 text-secondary" style={{ whiteSpace: 'pre-wrap' }}>{order.shippingAddress}</p>
            {order.additionalNotes && (
              <div className="mt-3">
                <strong>Notes:</strong>
                <p className="mb-0 text-secondary" style={{ whiteSpace: 'pre-wrap' }}>{order.additionalNotes}</p>
              </div>
            )}
          </section>
        </aside>
      </div>
    </div>
  );
}
