import Link from 'next/link';
import { submitCheckoutAction } from '@/app/checkout/actions';
import { calculateCheckoutTotals, getCheckoutItems, readCart } from '@/lib/cart';
import { formatPrice } from '@/lib/data';

export default async function CheckoutPage({
  searchParams,
}: {
  searchParams?: { error?: string };
}) {
  const cart = await readCart();
  let checkoutItems = [] as Awaited<ReturnType<typeof getCheckoutItems>>;
  let totals = { item_count: 0, subtotal: 0, shipping: 0, total: 0 };

  try {
    checkoutItems = cart.length > 0 ? await getCheckoutItems(cart) : [];
    totals = calculateCheckoutTotals(checkoutItems);
  } catch {
    checkoutItems = [];
    totals = { item_count: 0, subtotal: 0, shipping: 0, total: 0 };
  }

  return (
    <main>
      <section className="shop-intro">
        <div className="container py-5">
          <p className="eyebrow mb-2">Almost yours</p>
          <h1 className="section-title mb-2">Checkout</h1>
          <p className="text-secondary mb-0">Complete your details for Cash on Delivery.</p>
        </div>
      </section>

      <div className="container py-5">
        {searchParams?.error && <div className="alert alert-danger" role="alert">{searchParams.error}</div>}

        {checkoutItems.length === 0 ? (
          <div className="empty-cart border text-center p-5">
            <h2 className="section-title h1">Your cart is empty.</h2>
            <Link className="btn btn-dark" href="/shop">Continue shopping</Link>
          </div>
        ) : (
          <div className="row g-5 align-items-start">
            <div className="col-lg-7">
              <form action={submitCheckoutAction} method="post" className="bg-white border p-4" noValidate>
                <h2 className="h4 mb-4">Delivery details</h2>

                <div className="mb-3">
                  <label className="form-label" htmlFor="full_name">Full Name</label>
                  <input className="form-control" type="text" id="full_name" name="full_name" maxLength={120} required />
                </div>

                <div className="row g-3">
                  <div className="col-md-6">
                    <label className="form-label" htmlFor="email">Email</label>
                    <input className="form-control" type="email" id="email" name="email" maxLength={190} required />
                  </div>
                  <div className="col-md-6">
                    <label className="form-label" htmlFor="phone">Phone</label>
                    <input className="form-control" type="tel" id="phone" name="phone" maxLength={30} required />
                  </div>
                </div>

                <div className="mt-3 mb-3">
                  <label className="form-label" htmlFor="address">Address</label>
                  <textarea className="form-control" id="address" name="address" rows={4} minLength={5} required />
                </div>

                <div className="mb-3">
                  <label className="form-label" htmlFor="city">City</label>
                  <input className="form-control" type="text" id="city" name="city" maxLength={100} required />
                </div>

                <div className="mb-4">
                  <label className="form-label" htmlFor="additional_notes">Additional notes</label>
                  <textarea className="form-control" id="additional_notes" name="additional_notes" rows={3} maxLength={1000} />
                </div>

                <button className="btn btn-dark" type="submit">Place order</button>
              </form>
            </div>

            <aside className="col-lg-5">
              <div className="cart-summary border p-4">
                <p className="eyebrow mb-2">Order summary</p>
                <h2 className="h4 mb-4">Your order</h2>
                {checkoutItems.map((item) => (
                  <div key={item.itemKey} className="d-flex justify-content-between gap-3 border-bottom py-3">
                    <div>
                      <strong>{item.name}</strong>
                      {item.variantName && <small className="d-block text-secondary">Color: {item.variantName}</small>}
                      <small className="d-block text-secondary">Qty: {item.quantity}</small>
                    </div>
                    <strong>{formatPrice(item.subtotal)}</strong>
                  </div>
                ))}
                <div className="d-flex justify-content-between mt-4 small text-secondary">
                  <span>Subtotal</span>
                  <strong>{formatPrice(totals.subtotal)}</strong>
                </div>
                <div className="d-flex justify-content-between small text-secondary mt-2">
                  <span>Shipping</span>
                  <strong>{totals.shipping > 0 ? formatPrice(totals.shipping) : 'Free'}</strong>
                </div>
                <div className="d-flex justify-content-between h5 mt-3">
                  <span>Total</span>
                  <strong>{formatPrice(totals.total)}</strong>
                </div>
              </div>
            </aside>
          </div>
        )}
      </div>
    </main>
  );
}
