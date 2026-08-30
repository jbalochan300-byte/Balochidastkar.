import { NextResponse } from 'next/server';

export async function GET() {
  return NextResponse.json({
    status: 'ok',
    app: '[NEW WEBSITE NAME]',
    environment: process.env.NODE_ENV ?? 'development',
  });
}
