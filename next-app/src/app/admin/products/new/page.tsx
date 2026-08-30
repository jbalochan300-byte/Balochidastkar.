import { createProductAction } from '@/app/admin/products/actions';

export default function AdminNewProductPage({ searchParams }: { searchParams?: { error?: string } }) {
  return (
    <div className="container-fluid p-4 p-lg-5">
      <div className="mb-4">
        <p className="eyebrow mb-2">Catalog</p>
        <h1 className="section-title mb-2">Add product</h1>
        <p className="text-secondary mb-0">Create a new item for the Dastkar collection.</p>
      </div>

      {searchParams?.error && <div className="alert alert-danger" role="alert">{searchParams.error}</div>}

      <form action={createProductAction} method="post" encType="multipart/form-data" className="bg-white border rounded p-4">
        <div className="row g-4">
          <div className="col-lg-8">
            <h2 className="h5 mb-3">Product information</h2>
            <div className="mb-3">
              <label className="form-label" htmlFor="name">Product name</label>
              <input className="form-control" type="text" id="name" name="name" maxLength={150} required />
            </div>
            <div className="row g-3">
              <div className="col-md-6">
                <label className="form-label" htmlFor="sku">SKU</label>
                <input className="form-control" type="text" id="sku" name="sku" maxLength={100} required />
              </div>
              <div className="col-md-6">
                <label className="form-label" htmlFor="category">Category</label>
                <input className="form-control" type="text" id="category" name="category" maxLength={100} required />
              </div>
            </div>
            <div className="mb-3 mt-3">
              <label className="form-label" htmlFor="shortDescription">Short description</label>
              <textarea className="form-control" id="shortDescription" name="shortDescription" rows={3} maxLength={255} required />
            </div>
            <div className="mb-3">
              <label className="form-label" htmlFor="fullDescription">Full description</label>
              <textarea className="form-control" id="fullDescription" name="fullDescription" rows={7} required />
            </div>
            <div className="mb-3">
              <label className="form-label" htmlFor="mainImage">Main product image</label>
              <input className="form-control" type="file" id="mainImage" name="mainImage" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" />
            </div>
            <div className="mb-3">
              <label className="form-label" htmlFor="galleryImages">Gallery images</label>
              <input className="form-control" type="file" id="galleryImages" name="galleryImages" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple />
            </div>
          </div>

          <div className="col-lg-4">
            <h2 className="h5 mb-3">Pricing and inventory</h2>
            <div className="mb-3">
              <label className="form-label" htmlFor="price">Regular price (PKR)</label>
              <input className="form-control" type="number" id="price" name="price" min="0" step="0.01" required />
            </div>
            <div className="mb-3">
              <label className="form-label" htmlFor="salePrice">Sale price (PKR)</label>
              <input className="form-control" type="number" id="salePrice" name="salePrice" min="0" step="0.01" />
            </div>
            <div className="mb-3">
              <label className="form-label" htmlFor="stockQuantity">Stock quantity</label>
              <input className="form-control" type="number" id="stockQuantity" name="stockQuantity" min="0" step="1" defaultValue={0} required />
            </div>
            <div className="mb-3">
              <label className="form-label" htmlFor="status">Status</label>
              <select className="form-select" id="status" name="status" defaultValue="active">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div className="form-check mt-4">
              <input className="form-check-input" type="checkbox" id="isFeatured" name="isFeatured" value="1" />
              <label className="form-check-label" htmlFor="isFeatured">Feature this product</label>
            </div>
          </div>
        </div>

        <hr className="my-4" />
        <div className="d-flex justify-content-between align-items-center gap-2 mb-3">
          <div>
            <h2 className="h5 mb-1">Colors and variants</h2>
            <p className="text-secondary small mb-0">Add optional color choices with their own price adjustment and stock.</p>
          </div>
          <button className="btn btn-outline-dark btn-sm" type="button" onClick={(event) => {
            event.preventDefault();
            const container = document.getElementById('variantRows');
            if (!container) return;
            const nextIndex = container.children.length;
            const row = document.createElement('div');
            row.className = 'border rounded p-3 mb-3';
            row.innerHTML = `
              <div class="row g-3 align-items-end">
                <div class="col-md-4">
                  <label class="form-label" for="variantName_${nextIndex}">Color name</label>
                  <input class="form-control" type="text" id="variantName_${nextIndex}" name="variants[${nextIndex}][variantName]" maxlength="100" />
                </div>
                <div class="col-md-3">
                  <label class="form-label" for="additionalPrice_${nextIndex}">Additional price</label>
                  <input class="form-control" type="number" id="additionalPrice_${nextIndex}" name="variants[${nextIndex}][additionalPrice]" min="0" step="0.01" />
                </div>
                <div class="col-md-2">
                  <label class="form-label" for="stockQuantity_${nextIndex}">Stock</label>
                  <input class="form-control" type="number" id="stockQuantity_${nextIndex}" name="variants[${nextIndex}][stockQuantity]" min="0" step="1" value="0" />
                </div>
                <div class="col-md-2">
                  <label class="form-label" for="status_${nextIndex}">Status</label>
                  <select class="form-select" id="status_${nextIndex}" name="variants[${nextIndex}][status]">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>
                <div class="col-md-1 text-md-end">
                  <button class="btn btn-outline-danger btn-sm" type="button" onclick="this.closest('.border').remove()">Remove</button>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="variantImage_${nextIndex}">Color photo (optional)</label>
                  <input class="form-control" type="file" id="variantImage_${nextIndex}" name="variants[${nextIndex}][variantImage]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" />
                </div>
              </div>`;
            container.appendChild(row);
          }}>+ Add color</button>
        </div>

        <div id="variantRows">
          <div className="border rounded p-3 mb-3">
            <div className="row g-3 align-items-end">
              <div className="col-md-4">
                <label className="form-label" htmlFor="variantName_0">Color name</label>
                <input className="form-control" type="text" id="variantName_0" name="variants[0][variantName]" maxLength={100} />
              </div>
              <div className="col-md-3">
                <label className="form-label" htmlFor="additionalPrice_0">Additional price</label>
                <input className="form-control" type="number" id="additionalPrice_0" name="variants[0][additionalPrice]" min="0" step="0.01" />
              </div>
              <div className="col-md-2">
                <label className="form-label" htmlFor="stockQuantity_0">Stock</label>
                <input className="form-control" type="number" id="stockQuantity_0" name="variants[0][stockQuantity]" min="0" step="1" defaultValue={0} />
              </div>
              <div className="col-md-2">
                <label className="form-label" htmlFor="status_0">Status</label>
                <select className="form-select" id="status_0" name="variants[0][status]" defaultValue="active">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
              <div className="col-md-1 text-md-end">
                <button className="btn btn-outline-danger btn-sm" type="button" onClick={(event) => {
                  event.preventDefault();
                  const row = event.currentTarget.closest('.border');
                  if (row && document.querySelectorAll('#variantRows .border').length > 1) row.remove();
                }}>Remove</button>
              </div>
              <div className="col-md-6">
                <label className="form-label" htmlFor="variantImage_0">Color photo (optional)</label>
                <input className="form-control" type="file" id="variantImage_0" name="variants[0][variantImage]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" />
              </div>
            </div>
          </div>
        </div>

        <div className="d-flex justify-content-end gap-2 mt-4 pt-4 border-top">
          <a className="btn btn-outline-secondary" href="/admin/products">Cancel</a>
          <button className="btn btn-dark" type="submit">Save product</button>
        </div>
      </form>
    </div>
  );
}
