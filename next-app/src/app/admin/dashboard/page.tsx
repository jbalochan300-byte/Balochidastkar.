import { prisma } from '@/lib/prisma';
import { requireAdmin } from '@/lib/admin-auth';

function badgeClass(status: string) {
  switch (status) {
    case 'completed':
      return 'text-bg-success';
    case 'pending':
      return 'text-bg-warning';
    case 'cancelled':
      return 'text-bg-danger';
    default:
      return 'text-bg-secondary';
  }
}

export default async function AdminDashboardPage() {
  await requireAdmin();

  const [productCount, activeProductCount, totalOrderCount, pendingOrderCount, deliveredOrderCount, contactMessageCount, newsletterCount, recentOrders] = await Promise.all([
    prisma.product.count(),
    prisma.product.count({ where: { isActive: true } }),
    prisma.order.count(),
    prisma.order.count({ where: { status: 'pending' } }),
    prisma.order.count({ where: { status: 'delivered' } }),
    prisma.contactMessage.count(),
    prisma.newsletterSubscriber.count({ where: { isActive: true } }),
    prisma.order.findMany({
      orderBy: [{ createdAt: 'desc' }, { id: 'desc' }],
      take: 5,
      select: {
        orderNumber: true,
        customerName: true,
        totalAmount: true,
        status: true,
        createdAt: true,
      },
    }),
  ]);

  const dashboardStats = {
    total_products: productCount,
    active_products: activeProductCount,
    total_orders: totalOrderCount,
    pending_orders: pendingOrderCount,
    delivered_orders: deliveredOrderCount,
    contact_messages: contactMessageCount,
    newsletter_subscribers: newsletterCount,
  };

  const statCards = [
    ['Total Products', dashboardStats.total_products, 'clay'],
    ['Active Products', dashboardStats.active_products, 'sage'],
    ['Total Orders', dashboardStats.total_orders, 'gold'],
    ['Pending Orders', dashboardStats.pending_orders, 'orange'],
    ['Delivered Orders', dashboardStats.delivered_orders, 'green'],
    ['Contact Messages', dashboardStats.contact_messages, 'blue'],
    ['Newsletter Subscribers', dashboardStats.newsletter_subscribers, 'purple'],
  ];

  return (
    <div className="container-fluid p-4 p-lg-5">
      <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
          <p className="eyebrow mb-2">Overview</p>
          <h1 className="section-title mb-2">Dashboard</h1>
          <p className="text-secondary mb-0">A quick view of your [NEW WEBSITE NAME] store.</p>
        </div>
        <span className="text-secondary small">Updated {new Date().toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
      </div>

      <div className="row g-3 mb-5">
        {statCards.map(([label, value, accent]) => (
          <div key={String(label)} className="col-sm-6 col-xl-3">
            <div className={`admin-stat-card admin-stat-${accent}`}>
              <p>{String(label)}</p>
              <strong>{String(value)}</strong>
            </div>
          </div>
        ))}
      </div>

      <section className="admin-panel" aria-labelledby="recent-orders-heading">
        <div className="d-flex justify-content-between align-items-center gap-3 mb-3">
          <div>
            <p className="eyebrow mb-1">Latest activity</p>
            <h2 className="h4 mb-0" id="recent-orders-heading">Recent orders</h2>
          </div>
          <a className="btn btn-sm btn-outline-dark" href="/admin/orders">View all</a>
        </div>

        {recentOrders.length === 0 ? (
          <p className="text-secondary mb-0">No orders have been placed yet.</p>
        ) : (
          <div className="table-responsive">
            <table className="table align-middle mb-0">
              <thead>
                <tr>
                  <th scope="col">Order</th>
                  <th scope="col">Customer</th>
                  <th scope="col">Amount</th>
                  <th scope="col">Status</th>
                  <th scope="col">Date</th>
                </tr>
              </thead>
              <tbody>
                {recentOrders.map((order) => (
                  <tr key={order.orderNumber}>
                    <td className="fw-semibold">{order.orderNumber}</td>
                    <td>{order.customerName}</td>
                    <td>{new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', maximumFractionDigits: 0 }).format(Number(order.totalAmount.toString()))}</td>
                    <td><span className={`badge ${badgeClass(order.status)}`}>{order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></td>
                    <td className="text-secondary">{new Date(order.createdAt).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </div>
  );
}
