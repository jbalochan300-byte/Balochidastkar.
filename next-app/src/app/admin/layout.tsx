import Link from 'next/link';
import { redirect } from 'next/navigation';
import { getCurrentAdmin } from '@/lib/admin-auth';

const navigation = [
  { label: 'Dashboard', href: '/admin/dashboard' as const },
  { label: 'Products', href: '/admin/products' as const },
  { label: 'Orders', href: '/admin/orders' as const },
  { label: 'Messages', href: '/admin/messages' as const },
  { label: 'Newsletter', href: '/admin/newsletter' as const },
];

export default async function AdminLayout({ children }: { children: React.ReactNode }) {
  const admin = await getCurrentAdmin();

  if (!admin) {
    redirect('/admin/login');
  }

  return (
    <div className="admin-shell">
      <header className="admin-topbar">
        <div className="d-flex align-items-center gap-3">
          <a className="admin-brand" href="/admin/dashboard">
            <img className="admin-brand-logo" src="/images/logo-icon.png" alt="[NEW WEBSITE NAME] logo" />
            <span>
              [NEW WEBSITE NAME] <small>Admin</small>
            </span>
          </a>
        </div>
        <div className="d-flex align-items-center gap-3">
          <span className="admin-current-user d-none d-sm-inline text-secondary small">Signed in as <strong>{admin.name}</strong></span>
          <Link className="btn btn-outline-dark btn-sm" href="/">View website</Link>
          <Link className="btn btn-outline-danger btn-sm" href="/admin/logout">Logout</Link>
        </div>
      </header>

      <div className="admin-layout">
        <aside className="admin-sidebar offcanvas-lg offcanvas-start" tabIndex={-1} id="adminSidebar" aria-label="Admin navigation">
          <div className="offcanvas-header d-lg-none">
            <span className="admin-sidebar-title">Navigation</span>
            <button type="button" className="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close navigation" />
          </div>
          <div className="offcanvas-body d-flex flex-column p-0">
            <div className="admin-sidebar-heading d-none d-lg-block">Workspace</div>
            <nav className="admin-nav" aria-label="Primary admin navigation">
              {navigation.map((item) => (
                <a key={item.href} className="admin-nav-link" href={item.href}>
                  <span className="admin-nav-icon" aria-hidden="true">{item.label.charAt(0)}</span>
                  <span>{item.label}</span>
                </a>
              ))}
            </nav>
            <div className="mt-auto">
              <div className="admin-sidebar-divider" />
              <Link className="admin-nav-link admin-logout-link" href="/admin/logout">
                <span className="admin-nav-icon" aria-hidden="true">←</span>
                <span>Logout</span>
              </Link>
            </div>
          </div>
        </aside>
        <main className="admin-content">{children}</main>
      </div>
    </div>
  );
}
