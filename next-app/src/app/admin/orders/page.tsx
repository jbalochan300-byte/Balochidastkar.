import { requireAdmin } from '@/lib/admin-auth';
import { prisma } from '@/lib/prisma';
import Link from 'next/link';

function orderStatusClass(status: string) {
  switch (status) {
    case 'delivered': return 'text-bg-success';
    case 'cancelled': return 'text-bg-danger';
    case 'pending':
    case 'processing': return 'text-bg-warning';
    default: return 'text-bg-secondary';
  }
}

function paymentStatusClass(status: string) {
  switch (status) {
    case 'paid': return 'text-bg-success';
    case 'failed': return 'text-bg-danger';
    case 'cod_pending': return 'text-bg-info';
    default: return 'text-bg-warning';
  }
}

export default async function AdminOrdersPage({ searchParams }: { searchParams?: { search?: string; status?: string; payment_status?: string } }) {
  await requireAdmin();

  const search = String(searchParams?.search ?? '');
  const status = String(searchParams?.status ?? '');
  const paymentStatus = String(searchParams?.payment_status ?? '');

  const orders = await prisma.order.findMany({
    where: {
      ...(search ? {
        OR: [
          { orderNumber: { contains: search } },
          { customerName: { contains: search } },
          { customerEmail: { contains: search } },
        ],
      } : {}),
      ...(status ? { status } : {}),
      ...(paymentStatus ? { paymentStatus } : {}),
    },
    orderBy: [{ createdAt: 'desc' }, { id: 'desc' }],
  });

  const allowedOrderStatuses = ['pending','confirmed','processing','shipped','delivered','cancelled'];
  const allowedPaymentStatuses = ['pending','paid','failed','cod_pending'];

  return (
    <div className="container-fluid p-4 p-lg-5">
      <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
          <p className="eyebrow mb-2">Store activity</p>
          <h1 className="section-title mb-2">Orders</h1>
          <p className="text-secondary mb-0">Review customers, items, payments, and delivery progress.</p>
        </div>
      </div>

      <form className="bg-white border rounded p-3 mb-4" method="get" action="/admin/orders">
        <div className="row g-3 align-items-end">
          <div className="col-lg-5">
            <label className="form-label" htmlFor="search">Search orders</label>
            <input className="form-control" type="search" id="search" name="search" defaultValue={search} placeholder="Order number, name, or email" />
          </div>
          <div className="col-sm-6 col-lg-3">
            <label className="form-label" htmlFor="status">Order status</label>
            <select className="form-select" id="status" name="status" defaultValue={status}>
              <option value="">All statuses</option>
              {allowedOrderStatuses.map((option) => <option key={option} value={option}>{option.charAt(0).toUpperCase() + option.slice(1)}</option>)}
            </select>
          </div>
          <div className="col-sm-6 col-lg-2">
            <label className="form-label" htmlFor="payment_status">Payment</label>
            <select className="form-select" id="payment_status" name="payment_status" defaultValue={paymentStatus}>
              <option value="">All payments</option>
              {allowedPaymentStatuses.map((option) => <option key={option} value={option}>{option.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())}</option>)}
            </select>
          </div>
          <div className="col-lg-2 d-flex gap-2">
            <button className="btn btn-dark flex-grow-1" type="submit">Search</button>
            <a className="btn btn-outline-secondary" href="/admin/orders">Clear</a>
          </div>
        </div>
      </form>

      <div className="bg-white border rounded overflow-hidden">
        <div className="table-responsive">
          <table className="table align-middle mb-0">
            <thead className="table-light">
              <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Date</th>
                <th className="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              {orders.length === 0 ? (
                <tr><td colSpan={7} className="text-center text-secondary py-5">No orders match your search.</td></tr>
              ) : orders.map((order) => (
                <tr key={order.id}>
                  <td><strong>{order.orderNumber}</strong></td>
                  <td>{order.customerName}<small className="d-block text-secondary">{order.customerEmail}</small></td>
                  <td>{new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', maximumFractionDigits: 0 }).format(Number(order.totalAmount.toString()))}</td>
                  <td><span className={`badge ${paymentStatusClass(order.paymentStatus)}`}>{order.paymentStatus.replace('_',' ').replace(/\b\w/g, c => c.toUpperCase())}</span></td>
                  <td><span className={`badge ${orderStatusClass(order.status)}`}>{order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></td>
                  <td className="text-secondary">{new Date(order.createdAt).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</td>
                  <td className="text-end"><Link className="btn btn-sm btn-outline-dark" href={`/admin/orders/${order.id}`}>View</Link></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
