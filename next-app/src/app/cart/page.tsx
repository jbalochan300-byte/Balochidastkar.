import Link from 'next/link';
import { redirect } from 'next/navigation';
import { clearCartAction, removeCartAction, updateCartAction } from '@/app/cart/actions';
import { getCartTotals, readCart } from '@/lib/cart';
import { formatPrice } from '@/lib/data';

export default async function CartPage({
  searchParams,
}: {
  searchParams?: { error?: string };
}) {
  const cart = await readCart();
  const totals = await getCartTotals();

  if (searchParams?.error) {
    // no-op: server-rendered error message below
  }

  return (
    <main>
      <section className="shop-intro">
        <div className="container py-5">
          <p className="eyebrow mb-2">Your selection</p>
          <h1 className="section-title mb-2">Shopping cart</h1>
          <p className="text-secondary mb-0">{totals.item_count} item{totals.item_count === 1 ? '' : 's'} ready for their next chapter.</p>
        </div>
      </section>

      <div className="container py-5">
        {searchParams?.error && <div className="alert alert-danger" role="alert">{searchParams.error}</div>}

        {cart.length === 0 ? (
          <div className="empty-cart border text-center p-5">
            <p className="eyebrow">A quiet beginning</p>
            <h2 className="section-title h1">Your cart is empty.</h2>
            <p className="text-secondary">Find a piece that speaks to you from the collection.</p>
            <Link className="btn btn-dark mt-2" href="/shop">Continue shopping</Link>
          </div>
        ) : (
          <div className="row g-5 align-items-start">
            <div className="col-lg-8">
              <div className="cart-list border-top">
                {cart.map((item) => (
                  <article key={item.itemKey} className="cart-item border-bottom py-4">
                    <div className="row g-3 align-items-center">
                      <div className="col-4 col-md-2">
                        {item.imagePath ? (
                          <img className="cart-item-image" src={item.imagePath} alt={item.name} />
                        ) : (
                          <div className="cart-item-image cart-item-placeholder">BD</div>
                        )}
                      </div>
                      <div className="col-8 col-md-5">
                        <h2 className="h5 mb-1">{item.name}</h2>
                        {item.color && <p className="text-secondary mb-1">Color: {item.color}</p>}
                        <p className="text-secondary mb-0">SKU: {item.sku}</p>
                      </div>
                      <div className="col-6 col-md-2">
                        <form action={updateCartAction} method="post">
                          <input type="hidden" name="item_key" value={item.itemKey} />
                          <div className="cart-quantity-form">
                            <button className="quantity-button" type="submit" name="quantity" value={Math.max(1, item.quantity - 1)} aria-label="Decrease quantity">-</button>
                            <span>{item.quantity}</span>
                            <button className="quantity-button" type="submit" name="quantity" value={Math.min(item.stockQuantity, item.quantity + 1)} aria-label="Increase quantity">+</button>
                          </div>
                        </form>
                      </div>
                      <div className="col-6 col-md-2 text-md-end">
                        <span className="small text-secondary d-block d-md-none">Subtotal</span>
                        <strong>{formatPrice(item.price * item.quantity)}</strong>
                        <form action={removeCartAction} method="post" className="mt-2">
                          <input type="hidden" name="item_key" value={item.itemKey} />
                          <button className="btn btn-link btn-sm text-danger p-0" type="submit">Remove</button>
                        </form>
                      </div>
                    </div>
                  </article>
                ))}
              </div>

              <div className="d-flex flex-wrap justify-content-between gap-3 mt-4">
                <Link className="text-link" href="/shop">&larr; Continue shopping</Link>
                <form action={clearCartAction} method="post">
                  <button className="btn btn-link text-secondary p-0" type="submit">Clear cart</button>
                </form>
              </div>
            </div>

            <aside className="col-lg-4">
              <div className="cart-summary border p-4">
                <p className="eyebrow mb-2">Order summary</p>
                <h2 className="h4 mb-4">Your total</h2>
                <div className="d-flex justify-content-between small text-secondary mb-2">
                  <span>Subtotal</span>
                  <strong>{formatPrice(totals.subtotal)}</strong>
                </div>
                <div className="d-flex justify-content-between small text-secondary mb-3">
                  <span>Shipping</span>
                  <strong>{totals.shipping > 0 ? formatPrice(totals.shipping) : 'Free'}</strong>
                </div>
                <div className="d-flex justify-content-between h5 mb-4">
                  <span>Total</span>
                  <strong>{formatPrice(totals.total)}</strong>
                </div>
                <Link className="btn btn-dark w-100" href="/checkout">Proceed to checkout</Link>
              </div>
            </aside>
          </div>
        )}
      </div>
    </main>
  );
}
