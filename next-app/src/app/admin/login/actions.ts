"use server";

import { redirect } from 'next/navigation';
import { loginAdmin } from '@/lib/admin-auth';

export async function loginAdminAction(formData: FormData) {
  const email = String(formData.get('email') ?? '').trim();
  const password = String(formData.get('password') ?? '');

  if (!email || !password) {
    redirect('/admin/login?error=' + encodeURIComponent('Please enter your email and password.'));
  }

  const admin = await loginAdmin(email, password);
  if (!admin) {
    redirect('/admin/login?error=' + encodeURIComponent('Incorrect email or password.'));
  }

  redirect('/admin/dashboard');
}
