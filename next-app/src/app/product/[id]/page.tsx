import Link from 'next/link';
import { notFound } from 'next/navigation';
import { addToCartAction } from '@/app/cart/actions';
import { formatPrice, getProductById } from '@/lib/data';

export default async function ProductDetailPage({ params }: { params: { id: string } }) {
  const product = await getProductById(Number(params.id));

  if (!product) {
    notFound();
  }

  const regularPrice = product.price;
  const salePrice = product.salePrice && product.salePrice > 0 && product.salePrice < regularPrice ? product.salePrice : null;
  const basePrice = salePrice ?? regularPrice;
  const primaryImage = product.images[0]?.imagePath ?? product.imagePath ?? '';

  return (
    <main>
      <div className="container py-5">
        <div className="row g-5 align-items-start">
          <div className="col-lg-7">
            <div className="product-gallery-main mb-3">
              {primaryImage ? <img src={primaryImage} alt={product.name} /> : <div className="product-image-placeholder">[NEW WEBSITE NAME]</div>}
            </div>

            {product.images.length > 1 && (
              <div className="row g-2">
                {product.images.map((image) => (
                  <div key={`${image.imagePath}-${image.altText ?? 'thumb'}`} className="col-3 col-sm-2">
                    <button className="gallery-thumb" type="button">
                      <img src={image.imagePath} alt={image.altText ?? product.name} />
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="col-lg-5">
            <p className="eyebrow mb-2">[NEW WEBSITE NAME]</p>
            <h1 className="section-title mb-3">{product.name}</h1>
            <p className="text-secondary small mb-4">SKU: {product.sku}</p>
            <div className="product-detail-price mb-3">
              <strong>{formatPrice(basePrice)}</strong>
              {salePrice !== null && <del className="text-secondary ms-2">{formatPrice(regularPrice)}</del>}
            </div>
            <p className="product-detail-description mb-4">{product.fullDescription ?? product.description}</p>

            <form method="post" action={addToCartAction}>
              <input type="hidden" name="product_id" value={product.id} />
              <input type="hidden" name="intent" value="cart" />
              {product.variants.length > 0 && (
                <fieldset className="mb-4">
                  <legend className="form-label">Color</legend>
                  <div className="d-flex flex-wrap gap-2">
                    {product.variants.map((variant, index) => (
                      <label key={variant.id} className="btn btn-outline-dark" htmlFor={`color-${variant.id}`}>
                        <input id={`color-${variant.id}`} type="radio" name="color" value={variant.variantName} defaultChecked={index === 0} />
                        <span className="ms-2">{variant.variantName}</span>
                      </label>
                    ))}
                  </div>
                </fieldset>
              )}

              <p className="small mb-3">{product.stockQuantity > 0 ? 'In stock' : 'Out of stock'}</p>
              <div className="d-flex align-items-end gap-3 mb-4">
                <div>
                  <label className="form-label" htmlFor="quantity">Quantity</label>
                  <input className="form-control" type="number" id="quantity" name="quantity" defaultValue={1} min={1} max={product.stockQuantity} />
                </div>
                <div className="d-flex gap-2">
                  <button className="btn btn-dark btn-lg" type="submit" name="intent" value="cart">Add to Cart</button>
                  <button className="btn btn-outline-dark btn-lg" type="submit" name="intent" value="buy">Buy Now</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <section className="product-description-section border-top mt-5 pt-5">
          <p className="eyebrow">The details</p>
          <h2 className="section-title h2">Made to be remembered.</h2>
          <p className="text-secondary detail-copy">{product.fullDescription ?? product.description}</p>
        </section>
      </div>
    </main>
  );
}
