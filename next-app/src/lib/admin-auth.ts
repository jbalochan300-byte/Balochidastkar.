import crypto from 'node:crypto';
import { cookies } from 'next/headers';
import { redirect } from 'next/navigation';
import bcrypt from 'bcryptjs';
import { prisma } from '@/lib/prisma';

export type AdminSession = {
  id: number;
  name: string;
  email: string;
  role: string;
};

const SESSION_COOKIE_NAME = 'new_website_name_admin_session';
const SESSION_SECRET = process.env.ADMIN_SESSION_SECRET || 'local-admin-session-secret-change-me';

function hmac(value: string) {
  return crypto.createHmac('sha256', SESSION_SECRET).update(value).digest('hex');
}

function encodeSession(admin: AdminSession) {
  const payload = JSON.stringify({ id: admin.id, name: admin.name, email: admin.email, role: admin.role });
  return `${Buffer.from(payload, 'utf8').toString('base64url')}.${hmac(payload)}`;
}

function decodeSession(raw: string | undefined) {
  if (!raw) return null;
  const separatorIndex = raw.lastIndexOf('.');
  if (separatorIndex < 0) return null;

  const payload = raw.slice(0, separatorIndex);
  const signature = raw.slice(separatorIndex + 1);
  if (signature !== hmac(Buffer.from(payload, 'base64url').toString('utf8'))) {
    return null;
  }

  try {
    const parsed = JSON.parse(Buffer.from(payload, 'base64url').toString('utf8')) as Partial<AdminSession>;
    if (!parsed.id || !parsed.email) return null;
    return {
      id: Number(parsed.id),
      name: String(parsed.name ?? 'Admin'),
      email: String(parsed.email),
      role: String(parsed.role ?? 'admin'),
    } satisfies AdminSession;
  } catch {
    return null;
  }
}

export async function getCurrentAdmin(): Promise<AdminSession | null> {
  const cookieStore = await cookies();
  const raw = cookieStore.get(SESSION_COOKIE_NAME)?.value;
  const session = decodeSession(raw);

  if (!session) {
    return null;
  }

  const admin = await prisma.admin.findUnique({
    where: { id: session.id },
    select: { id: true, name: true, email: true, role: true, isActive: true },
  });

  if (!admin || !admin.isActive) {
    return null;
  }

  return {
    id: admin.id,
    name: admin.name,
    email: admin.email,
    role: admin.role,
  };
}

export async function requireAdmin(): Promise<AdminSession> {
  const admin = await getCurrentAdmin();
  if (!admin) {
    redirect('/admin/login');
  }
  return admin;
}

export async function loginAdmin(email: string, password: string): Promise<AdminSession | null> {
  const trimmedEmail = email.trim();
  if (!trimmedEmail || !password) return null;

  const admin = await prisma.admin.findUnique({
    where: { email: trimmedEmail },
  });

  if (!admin || !admin.isActive) {
    return null;
  }

  const passwordValid = await bcrypt.compare(password, admin.passwordHash);
  if (!passwordValid) {
    return null;
  }

  const session = {
    id: admin.id,
    name: admin.name,
    email: admin.email,
    role: admin.role,
  } satisfies AdminSession;

  const cookieStore = await cookies();
  cookieStore.set(SESSION_COOKIE_NAME, encodeSession(session), {
    path: '/',
    httpOnly: true,
    sameSite: 'lax',
    secure: false,
    maxAge: 60 * 60 * 8,
  });

  await prisma.admin.update({
    where: { id: admin.id },
    data: { lastLoginAt: new Date() },
  });

  return session;
}

export async function logoutAdmin() {
  const cookieStore = await cookies();
  cookieStore.delete(SESSION_COOKIE_NAME);
}
