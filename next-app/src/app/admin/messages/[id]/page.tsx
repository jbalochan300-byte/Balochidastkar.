import Link from 'next/link';
import { notFound } from 'next/navigation';
import { requireAdmin } from '@/lib/admin-auth';
import { prisma } from '@/lib/prisma';

export default async function AdminMessageDetailsPage({ params }: { params: { id: string } }) {
  await requireAdmin();

  const messageId = Number(params.id);
  if (!messageId) notFound();

  const message = await prisma.contactMessage.findUnique({ where: { id: messageId } });
  if (!message) notFound();

  return (
    <div className="container-fluid p-4 p-lg-5">
      <div className="d-flex justify-content-between align-items-end gap-3 mb-4">
        <div>
          <p className="eyebrow mb-2">Inbox</p>
          <h1 className="section-title mb-2">Message details</h1>
        </div>
        <Link className="btn btn-outline-secondary" href="/admin/messages">Back to messages</Link>
      </div>

      <div className="row g-4">
        <div className="col-lg-8">
          <section className="bg-white border rounded p-4">
            <div className="d-flex justify-content-between gap-3 mb-4">
              <div>
                <p className="eyebrow mb-1">Subject</p>
                <h2 className="h3">{message.subject || 'No subject'}</h2>
              </div>
              <span className="badge text-bg-secondary align-self-start">{message.status.charAt(0).toUpperCase() + message.status.slice(1)}</span>
            </div>
            <p className="mb-0" style={{ whiteSpace: 'pre-wrap' }}>{message.message}</p>
          </section>

          <section className="bg-white border rounded p-4 mt-4">
            <h2 className="h5 mb-3">Your reply</h2>
            {message.adminReply && (
              <div className="alert alert-light border mb-3">
                <p className="small text-secondary mb-1">
                  Saved reply {message.repliedAt && `— ${new Date(message.repliedAt).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).replace(',', '')}`}
                </p>
                <p className="mb-0" style={{ whiteSpace: 'pre-wrap' }}>{message.adminReply}</p>
              </div>
            )}

            <form action="/admin/messages/update" method="post">
              <input type="hidden" name="messageId" value={message.id} />
              <div className="mb-3">
                <label className="form-label" htmlFor="admin_reply">Write your reply</label>
                <textarea className="form-control" id="admin_reply" name="adminReply" rows={5} defaultValue={message.adminReply || ''} placeholder="Type what you want to say back to this customer..." />
              </div>
              <div className="d-flex flex-wrap gap-2">
                <button className="btn btn-dark" type="submit">Save reply</button>
                <button className="btn btn-outline-dark" type="button" onClick={() => {
                  const subject = encodeURIComponent(`Re: ${message.subject || 'Your message to [NEW WEBSITE NAME]'}`);
                  const body = encodeURIComponent((document.getElementById('admin_reply') as HTMLTextAreaElement | null)?.value || '');
                  window.location.href = `mailto:${encodeURIComponent(message.email)}?subject=${subject}&body=${body}`;
                }}>Send by email</button>
              </div>
            </form>
          </section>
        </div>

        <aside className="col-lg-4">
          <section className="bg-white border rounded p-4 mb-4">
            <h2 className="h5 mb-3">Sender</h2>
            <p className="mb-1"><strong>{message.name}</strong></p>
            <p className="small text-secondary mb-1">{message.email}</p>
            <p className="small text-secondary mb-0">{message.phone || 'No phone provided'}</p>
          </section>

          <section className="bg-white border rounded p-4">
            <h2 className="h5 mb-3">Actions</h2>
            <form action="/admin/messages/update" method="post" className="d-grid gap-2">
              <input type="hidden" name="messageId" value={message.id} />
              <button className="btn btn-outline-dark" type="submit" name="action" value="read">Mark read</button>
              <button className="btn btn-outline-success" type="submit" name="action" value="replied">Mark replied</button>
              <button className="btn btn-outline-danger" type="submit" name="action" value="delete" onClick={(event) => {
                const shouldDelete = window.confirm('Delete this message?');
                if (!shouldDelete) event.preventDefault();
              }}>Delete message</button>
            </form>
          </section>
        </aside>
      </div>
    </div>
  );
}
