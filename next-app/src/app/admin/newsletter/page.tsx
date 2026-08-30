import { requireAdmin } from '@/lib/admin-auth';
import { prisma } from '@/lib/prisma';
import { toggleNewsletterStatusAction, deleteNewsletterSubscriberAction } from '@/app/admin/newsletter/actions';

export default async function AdminNewsletterPage({ searchParams }: { searchParams?: { search?: string; active?: string } }) {
  await requireAdmin();

  const search = String(searchParams?.search ?? '');
  const activeFilter = String(searchParams?.active ?? '');

  const subscribers = await prisma.newsletterSubscriber.findMany({
    where: {
      ...(search ? { email: { contains: search } } : {}),
      ...(activeFilter ? { isActive: activeFilter === 'active' } : {}),
    },
    orderBy: [{ subscribedAt: 'desc' }, { id: 'desc' }],
  });

  return (
    <div className="container-fluid p-4 p-lg-5">
      <div className="mb-4">
        <p className="eyebrow mb-2">Audience</p>
        <h1 className="section-title mb-2">Newsletter</h1>
        <p className="text-secondary mb-0">Manage people who want to hear from the collection.</p>
      </div>

      <form className="bg-white border rounded p-3 mb-4" method="get" action="/admin/newsletter">
        <div className="row g-3 align-items-end">
          <div className="col-lg-6">
            <label className="form-label" htmlFor="search">Search email</label>
            <input className="form-control" type="search" id="search" name="search" defaultValue={search} placeholder="subscriber@example.com" />
          </div>
          <div className="col-lg-3">
            <label className="form-label" htmlFor="active">Status</label>
            <select className="form-select" id="active" name="active" defaultValue={activeFilter}>
              <option value="">All subscribers</option>
              <option value="active">Active</option>
              <option value="inactive">Unsubscribed</option>
            </select>
          </div>
          <div className="col-lg-3 d-flex gap-2">
            <button className="btn btn-dark flex-grow-1" type="submit">Search</button>
            <a className="btn btn-outline-secondary" href="/admin/newsletter">Clear</a>
          </div>
        </div>
      </form>

      <div className="bg-white border rounded overflow-hidden">
        <div className="table-responsive">
          <table className="table align-middle mb-0">
            <thead className="table-light">
              <tr>
                <th>Email</th>
                <th>Status</th>
                <th>Subscribed</th>
                <th>Unsubscribed</th>
                <th className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {subscribers.length === 0 ? (
                <tr><td colSpan={5} className="text-center text-secondary py-5">No subscribers found.</td></tr>
              ) : subscribers.map((subscriber) => (
                <tr key={subscriber.id}>
                  <td>{subscriber.email}</td>
                  <td><span className={`badge ${subscriber.isActive ? 'text-bg-success' : 'text-bg-secondary'}`}>{subscriber.isActive ? 'Active' : 'Unsubscribed'}</span></td>
                  <td>{new Date(subscriber.subscribedAt).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</td>
                  <td>{subscriber.unsubscribedAt ? new Date(subscriber.unsubscribedAt).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'}</td>
                  <td className="text-end text-nowrap">
                    <form action={toggleNewsletterStatusAction} method="post" className="d-inline">
                      <input type="hidden" name="subscriberId" value={subscriber.id} />
                      <button className={`btn btn-sm ${subscriber.isActive ? 'btn-outline-secondary' : 'btn-outline-dark'}`} type="submit">
                        {subscriber.isActive ? 'Unsubscribe' : 'Reactivate'}
                      </button>
                    </form>
                    <form action={deleteNewsletterSubscriberAction} method="post" className="d-inline ms-2" onSubmit={(e) => {
                      if (!window.confirm('Delete this subscriber?')) e.preventDefault();
                    }}>
                      <input type="hidden" name="subscriberId" value={subscriber.id} />
                      <button className="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                    </form>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
