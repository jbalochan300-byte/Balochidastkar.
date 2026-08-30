import Link from 'next/link';
import { requireAdmin } from '@/lib/admin-auth';
import { prisma } from '@/lib/prisma';
import { deleteProductAction } from '@/app/admin/products/actions';

export default async function AdminProductsPage({
  searchParams,
}: {
  searchParams?: { search?: string; status?: string; featured?: string; category?: string };
}) {
  await requireAdmin();

  const filters = {
    search: String(searchParams?.search ?? ''),
    status: String(searchParams?.status ?? ''),
    featured: String(searchParams?.featured ?? ''),
    category: String(searchParams?.category ?? ''),
  };

  const categories = await prisma.product.findMany({
    where: { category: { not: '' } },
    select: { category: true },
    distinct: ['category'],
    orderBy: { category: 'asc' },
  });

  const where: any = {};
  if (filters.search) {
    where.OR = [
      { name: { contains: filters.search } },
      { sku: { contains: filters.search } },
    ];
  }
  if (filters.status === 'active' || filters.status === 'inactive') {
    where.isActive = filters.status === 'active';
  }
  if (filters.featured === 'yes' || filters.featured === 'no') {
    where.isFeatured = filters.featured === 'yes';
  }
  if (filters.category) {
    where.category = filters.category;
  }

  const products = await prisma.product.findMany({
    where,
    orderBy: [{ createdAt: 'desc' }, { id: 'desc' }],
    include: {
      productImages: {
        orderBy: [{ sortOrder: 'asc' }, { id: 'asc' }],
        take: 1,
      },
    },
  });

  return (
    <div className="container-fluid p-4 p-lg-5">
      <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
          <p className="eyebrow mb-2">Catalog</p>
          <h1 className="section-title mb-2">Products</h1>
          <p className="text-secondary mb-0">Manage your Dastkar collection and stock.</p>
        </div>
        <Link className="btn btn-dark" href="/admin/products/new">+ Add Product</Link>
      </div>

      <form className="bg-white border rounded p-3 mb-4" method="get" action="/admin/products">
        <div className="row g-3 align-items-end">
          <div className="col-lg-4">
            <label className="form-label" htmlFor="search">Search</label>
            <input className="form-control" type="search" id="search" name="search" defaultValue={filters.search} placeholder="Name or SKU" />
          </div>
          <div className="col-sm-6 col-lg-2">
            <label className="form-label" htmlFor="status">Status</label>
            <select className="form-select" id="status" name="status" defaultValue={filters.status}>
              <option value="">All statuses</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div className="col-sm-6 col-lg-2">
            <label className="form-label" htmlFor="featured">Featured</label>
            <select className="form-select" id="featured" name="featured" defaultValue={filters.featured}>
              <option value="">All products</option>
              <option value="yes">Featured</option>
              <option value="no">Not featured</option>
            </select>
          </div>
          <div className="col-sm-6 col-lg-2">
            <label className="form-label" htmlFor="category">Category</label>
            <select className="form-select" id="category" name="category" defaultValue={filters.category}>
              <option value="">All categories</option>
              {categories.map((category) => (
                <option key={category.category} value={category.category}>{category.category}</option>
              ))}
            </select>
          </div>
          <div className="col-sm-6 col-lg-2 d-flex gap-2">
            <button className="btn btn-dark flex-grow-1" type="submit">Filter</button>
            <a className="btn btn-outline-secondary" href="/admin/products">Clear</a>
          </div>
        </div>
      </form>

      <div className="bg-white border rounded overflow-hidden">
        <div className="table-responsive">
          <table className="table align-middle mb-0">
            <thead className="table-light">
              <tr>
                <th scope="col">Image</th>
                <th scope="col">Name</th>
                <th scope="col">SKU</th>
                <th scope="col">Price</th>
                <th scope="col">Sale price</th>
                <th scope="col">Stock</th>
                <th scope="col">Status</th>
                <th scope="col">Featured</th>
                <th scope="col" className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {products.length === 0 ? (
                <tr><td className="text-center text-secondary py-5" colSpan={9}>No products match the selected filters.</td></tr>
              ) : (
                products.map((product) => (
                  <tr key={product.id}>
                    <td>
                      {product.productImages[0]?.imagePath ? (
                        <img src={product.productImages[0].imagePath} alt={product.name} width={52} height={52} className="rounded object-fit-cover" />
                      ) : (
                        <span className="d-inline-flex align-items-center justify-content-center bg-light text-secondary rounded" style={{ width: 52, height: 52 }}>N/A</span>
                      )}
                    </td>
                    <td>
                      <strong>{product.name}</strong>
                      <small className="d-block text-secondary">{product.category}</small>
                    </td>
                    <td>{product.sku}</td>
                    <td>{new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', maximumFractionDigits: 0 }).format(Number(product.price.toString()))}</td>
                    <td>{product.salePrice ? new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', maximumFractionDigits: 0 }).format(Number(product.salePrice.toString())) : <span className="text-secondary">-</span>}</td>
                    <td>{product.stockQuantity}</td>
                    <td><span className={`badge ${product.isActive ? 'text-bg-success' : 'text-bg-secondary'}`}>{product.isActive ? 'Active' : 'Inactive'}</span></td>
                    <td>{product.isFeatured ? <span className="badge text-bg-warning">Yes</span> : <span className="text-secondary">No</span>}</td>
                    <td className="text-end text-nowrap">
                      <Link className="btn btn-sm btn-outline-dark" href={`/admin/products/${product.id}/edit`}>Edit</Link>
                      <form action={deleteProductAction} method="post" className="d-inline">
                        <input type="hidden" name="productId" value={product.id} />
                        <button type="submit" className="btn btn-sm btn-outline-danger ms-2" onClick={(event) => {
                          const shouldDelete = window.confirm('Delete this product and all its variants and images?');
                          if (!shouldDelete) event.preventDefault();
                        }}>
                          Delete
                        </button>
                      </form>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
