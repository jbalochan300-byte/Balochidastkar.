import { NextResponse } from 'next/server';
import { logoutAdmin } from '@/lib/admin-auth';

export async function GET() {
  await logoutAdmin();
  return NextResponse.redirect(new URL('/admin/login?logged_out=1', process.env.APP_URL || 'http://localhost:3000'));
}
