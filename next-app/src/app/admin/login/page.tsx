import Link from 'next/link';
import { loginAdminAction } from '@/app/admin/login/actions';

export default function AdminLoginPage({
  searchParams,
}: {
  searchParams?: { error?: string; logged_out?: string };
}) {
  return (
    <div className="admin-login-shell d-flex align-items-center justify-content-center min-vh-100 py-5">
      <div className="admin-login-card">
        <div className="text-center mb-4">
          <img className="admin-login-logo" src="/images/logo-icon.png" alt="[NEW WEBSITE NAME] logo" />
          <h1 className="admin-login-title mt-3 mb-1">[NEW WEBSITE NAME]</h1>
          <p className="text-secondary mb-0">Admin panel sign in</p>
        </div>
        {searchParams?.logged_out === '1' && <div className="alert alert-success" role="alert">You have been signed out.</div>}
        {searchParams?.error && <div className="alert alert-danger" role="alert">{searchParams.error}</div>}

        <form action={loginAdminAction} method="post" noValidate>
          <div className="mb-3">
            <label className="form-label" htmlFor="email">Email address</label>
            <input className="form-control" type="email" id="email" name="email" maxLength={190} required autoFocus />
          </div>
          <div className="mb-4">
            <label className="form-label" htmlFor="password">Password</label>
            <input className="form-control" type="password" id="password" name="password" maxLength={255} required />
          </div>
          <button className="btn btn-dark w-100" type="submit">Sign in</button>
        </form>
        <div className="text-center mt-4">
          <Link className="text-link" href="/">← Back to the website</Link>
        </div>
      </div>
    </div>
  );
}
