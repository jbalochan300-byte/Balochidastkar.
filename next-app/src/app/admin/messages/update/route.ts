import { NextResponse } from 'next/server';
import { requireAdmin } from '@/lib/admin-auth';
import { prisma } from '@/lib/prisma';

export async function POST(request: Request) {
  await requireAdmin();

  const formData = await request.formData();
  const messageId = Number(formData.get('messageId') ?? 0);
  const action = String(formData.get('action') ?? 'reply');

  if (!messageId) {
    return NextResponse.redirect(new URL('/admin/messages?error=' + encodeURIComponent('Invalid message selected.'), process.env.APP_URL || 'http://localhost:3000'));
  }

  if (action === 'delete') {
    await prisma.contactMessage.delete({ where: { id: messageId } });
    return NextResponse.redirect(new URL('/admin/messages', process.env.APP_URL || 'http://localhost:3000'));
  }

  if (action === 'read') {
    await prisma.contactMessage.update({ where: { id: messageId }, data: { status: 'read' } });
    return NextResponse.redirect(new URL('/admin/messages/' + messageId, process.env.APP_URL || 'http://localhost:3000'));
  }

  if (action === 'replied') {
    await prisma.contactMessage.update({ where: { id: messageId }, data: { status: 'replied' } });
    return NextResponse.redirect(new URL('/admin/messages/' + messageId, process.env.APP_URL || 'http://localhost:3000'));
  }

  const adminReply = String(formData.get('adminReply') ?? '').trim();
  if (!adminReply) {
    return NextResponse.redirect(new URL('/admin/messages/' + messageId + '?error=' + encodeURIComponent('Please write a reply before saving it.'), process.env.APP_URL || 'http://localhost:3000'));
  }

  await prisma.contactMessage.update({
    where: { id: messageId },
    data: {
      adminReply,
      repliedAt: new Date(),
      status: 'replied',
    },
  });

  return NextResponse.redirect(new URL('/admin/messages/' + messageId, process.env.APP_URL || 'http://localhost:3000'));
}
