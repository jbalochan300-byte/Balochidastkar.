import Link from 'next/link';
import { requireAdmin } from '@/lib/admin-auth';
import { prisma } from '@/lib/prisma';

export default async function AdminMessagesPage({ searchParams }: { searchParams?: { search?: string; status?: string } }) {
  await requireAdmin();

  const search = String(searchParams?.search ?? '');
  const status = String(searchParams?.status ?? '');
  const statuses = ['new', 'read', 'replied', 'archived'];

  const messages = await prisma.contactMessage.findMany({
    where: {
      ...(search ? {
        OR: [
          { name: { contains: search } },
          { email: { contains: search } },
          { subject: { contains: search } },
        ],
      } : {}),
      ...(status ? { status } : {}),
    },
    orderBy: [{ createdAt: 'desc' }, { id: 'desc' }],
  });

  return (
    <div className="container-fluid p-4 p-lg-5">
      <div className="mb-4">
        <p className="eyebrow mb-2">Inbox</p>
        <h1 className="section-title mb-2">Messages</h1>
        <p className="text-secondary mb-0">Read and manage customer enquiries.</p>
      </div>

      <form className="bg-white border rounded p-3 mb-4" method="get" action="/admin/messages">
        <div className="row g-3 align-items-end">
          <div className="col-lg-7">
            <label className="form-label" htmlFor="search">Search messages</label>
            <input className="form-control" type="search" id="search" name="search" defaultValue={search} placeholder="Name, email, or subject" />
          </div>
          <div className="col-lg-3">
            <label className="form-label" htmlFor="status">Status</label>
            <select className="form-select" id="status" name="status" defaultValue={status}>
              <option value="">All statuses</option>
              {statuses.map((option) => <option key={option} value={option}>{option.charAt(0).toUpperCase() + option.slice(1)}</option>)}
            </select>
          </div>
          <div className="col-lg-2 d-flex gap-2">
            <button className="btn btn-dark flex-grow-1" type="submit">Search</button>
            <a className="btn btn-outline-secondary" href="/admin/messages">Clear</a>
          </div>
        </div>
      </form>

      <div className="bg-white border rounded overflow-hidden">
        <div className="table-responsive">
          <table className="table align-middle mb-0">
            <thead className="table-light">
              <tr>
                <th>From</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Date</th>
                <th className="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              {messages.length === 0 ? (
                <tr><td colSpan={5} className="text-center text-secondary py-5">No messages found.</td></tr>
              ) : messages.map((message) => (
                <tr key={message.id}>
                  <td>
                    <strong>{message.name}</strong>
                    <small className="d-block text-secondary">{message.email}</small>
                    <small className="d-block text-secondary">{message.phone || '—'}</small>
                  </td>
                  <td>{message.subject || 'No subject'}</td>
                  <td><span className={`badge ${message.status === 'replied' ? 'text-bg-success' : message.status === 'read' ? 'text-bg-info' : 'text-bg-secondary'}`}>{message.status.charAt(0).toUpperCase() + message.status.slice(1)}</span></td>
                  <td className="text-secondary">{new Date(message.createdAt).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</td>
                  <td className="text-end"><Link className="btn btn-sm btn-outline-dark" href={`/admin/messages/${message.id}`}>View</Link></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
