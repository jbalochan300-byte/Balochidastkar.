"use server";

import { revalidatePath } from 'next/cache';
import { redirect } from 'next/navigation';
import { requireAdmin } from '@/lib/admin-auth';
import { prisma } from '@/lib/prisma';

export async function toggleNewsletterStatusAction(formData: FormData) {
  await requireAdmin();

  const subscriberId = Number(formData.get('subscriberId') ?? 0);
  if (!subscriberId) {
    redirect('/admin/newsletter?error=' + encodeURIComponent('Invalid subscriber selected.'));
  }

  const subscriber = await prisma.newsletterSubscriber.findUnique({ where: { id: subscriberId } });
  if (!subscriber) {
    redirect('/admin/newsletter?error=' + encodeURIComponent('Subscriber not found.'));
  }

  await prisma.newsletterSubscriber.update({
    where: { id: subscriberId },
    data: {
      isActive: !subscriber.isActive,
      unsubscribedAt: subscriber.isActive ? new Date() : null,
    },
  });

  revalidatePath('/admin/newsletter');
  redirect('/admin/newsletter');
}

export async function deleteNewsletterSubscriberAction(formData: FormData) {
  await requireAdmin();

  const subscriberId = Number(formData.get('subscriberId') ?? 0);
  if (!subscriberId) {
    redirect('/admin/newsletter?error=' + encodeURIComponent('Invalid subscriber selected.'));
  }

  const subscriber = await prisma.newsletterSubscriber.findUnique({ where: { id: subscriberId } });
  if (!subscriber) {
    redirect('/admin/newsletter?error=' + encodeURIComponent('Subscriber not found.'));
  }

  await prisma.newsletterSubscriber.delete({ where: { id: subscriberId } });

  revalidatePath('/admin/newsletter');
  redirect('/admin/newsletter');
}
