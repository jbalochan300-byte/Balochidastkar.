import Link from 'next/link';
import { getCategories, getColors, getProducts, formatPrice } from '@/lib/data';

export default async function ShopPage({
  searchParams,
}: {
  searchParams?: Record<string, string | string[] | undefined>;
}) {
  const search = typeof searchParams?.search === 'string' ? searchParams.search : '';
  const category = typeof searchParams?.category === 'string' ? searchParams.category : '';
  const color = typeof searchParams?.color === 'string' ? searchParams.color : '';
  const minPrice = typeof searchParams?.min_price === 'string' ? searchParams.min_price : '';
  const maxPrice = typeof searchParams?.max_price === 'string' ? searchParams.max_price : '';
  const sort = typeof searchParams?.sort === 'string' ? searchParams.sort : 'newest';
  const page = Number(searchParams?.page ?? '1') || 1;

  const [categories, colors, productsResult] = await Promise.all([
    getCategories(),
    getColors(),
    getProducts({ search, category, color, minPrice, maxPrice, sort, page }),
  ]);

  const buildQuery = (extra: Record<string, string | number | undefined>) => {
    const params = new URLSearchParams({
      search,
      category,
      color,
      min_price: minPrice,
      max_price: maxPrice,
      sort,
    });

    for (const [key, value] of Object.entries(extra)) {
      if (value !== undefined && value !== '') {
        params.set(key, String(value));
      }
    }

    return params.toString();
  };

  return (
    <main>
      <section className="shop-intro">
        <div className="container py-5">
          <p className="eyebrow mb-2">The collection</p>
          <h1 className="section-title mb-2">Shop the edit</h1>
          <p className="text-secondary mb-0">Traditional pieces from [NEW WEBSITE NAME], selected for a contemporary wardrobe.</p>
        </div>
      </section>

      <div className="container py-5">
        <div className="row g-4">
          <aside className="col-lg-3">
            <div className="shop-filters bg-white border p-4">
              <div className="d-flex justify-content-between align-items-center mb-3">
                <h2 className="h5 mb-0">Refine</h2>
                <Link className="small text-link" href="/shop">Clear</Link>
              </div>

              <form method="get" action="/shop">
                <div className="mb-3">
                  <label className="form-label" htmlFor="shopSearch">Search</label>
                  <input className="form-control" type="search" id="shopSearch" name="search" defaultValue={search} placeholder="Name or SKU" />
                </div>

                <div className="mb-3">
                  <label className="form-label" htmlFor="category">Category</label>
                  <select className="form-select" id="category" name="category" defaultValue={category}>
                    <option value="">All categories</option>
                    {categories.map((item) => (
                      <option key={item} value={item}>{item}</option>
                    ))}
                  </select>
                </div>

                <div className="mb-3">
                  <label className="form-label" htmlFor="color">Color</label>
                  <select className="form-select" id="color" name="color" defaultValue={color}>
                    <option value="">All colors</option>
                    {colors.map((item) => (
                      <option key={item} value={item}>{item}</option>
                    ))}
                  </select>
                </div>

                <div className="row g-2">
                  <div className="col-6">
                    <label className="form-label" htmlFor="min_price">Min price</label>
                    <input className="form-control" type="number" min="0" step="1" id="min_price" name="min_price" defaultValue={minPrice} />
                  </div>
                  <div className="col-6">
                    <label className="form-label" htmlFor="max_price">Max price</label>
                    <input className="form-control" type="number" min="0" step="1" id="max_price" name="max_price" defaultValue={maxPrice} />
                  </div>
                </div>

                <button className="btn btn-dark w-100 mt-4" type="submit">Apply filters</button>
              </form>
            </div>
          </aside>

          <section className="col-lg-9">
            <div className="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
              <p className="text-secondary mb-0">{productsResult.total} pieces available</p>
              <form method="get" action="/shop">
                <input type="hidden" name="search" value={search} />
                <input type="hidden" name="category" value={category} />
                <input type="hidden" name="color" value={color} />
                <input type="hidden" name="min_price" value={minPrice} />
                <input type="hidden" name="max_price" value={maxPrice} />
                <select className="form-select" name="sort" defaultValue={sort} onChange={(event) => event.currentTarget.form?.submit()}>
                  <option value="newest">Newest</option>
                  <option value="featured">Featured</option>
                  <option value="price_low">Price: low to high</option>
                  <option value="price_high">Price: high to low</option>
                </select>
              </form>
            </div>

            <div className="row g-4">
              {productsResult.products.map((product) => (
                <div key={product.id} className="col-md-6 col-lg-4">
                  <article className="product-card h-100">
                    <Link className="product-image" href={`/product/${product.id}`}>
                      {product.imagePath ? <img src={product.imagePath} alt={product.name} /> : <span className="product-image-placeholder">BD</span>}
                    </Link>
                    <div className="p-3 p-lg-4">
                      <p className="eyebrow mb-2">{product.category}</p>
                      <h3 className="h4 mb-3">
                        <Link className="text-decoration-none text-reset" href={`/product/${product.id}`}>{product.name}</Link>
                      </h3>
                      <div className="d-flex align-items-center gap-2">
                        <strong>{formatPrice(product.salePrice ?? product.price)}</strong>
                        {product.salePrice !== null && <del className="text-secondary small">{formatPrice(product.price)}</del>}
                      </div>
                    </div>
                  </article>
                </div>
              ))}
            </div>

            {productsResult.totalPages > 1 && (
              <nav className="mt-4 d-flex justify-content-center gap-3" aria-label="Shop pagination">
                {Array.from({ length: productsResult.totalPages }, (_, index) => index + 1).map((pageNumber) => (
                  <Link
                    key={pageNumber}
                    className={pageNumber === productsResult.page ? 'btn btn-dark' : 'btn btn-outline-dark'}
                    href={`/shop?${buildQuery({ page: pageNumber })}`}
                  >
                    {pageNumber}
                  </Link>
                ))}
              </nav>
            )}
          </section>
        </div>
      </div>
    </main>
  );
}
