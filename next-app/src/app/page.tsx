import Link from 'next/link';
import { formatPrice, getFeaturedProducts, getStoreStats } from '@/lib/data';

export default async function HomePage() {
  const [featuredProducts, stats] = await Promise.all([getFeaturedProducts(), getStoreStats()]);

  return (
    <main>
      <section className="hero-banner" style={{ backgroundImage: "url('https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=1600&q=80')" }}>
        <div className="hero-banner-overlay" />
        <div className="container hero-banner-content">
          <div className="row">
            <div className="col-lg-7">
              <p className="eyebrow">Handmade Balochi Collection</p>
              <h1 className="display-title hero-banner-title">Traditional clothes and beautiful handmade pieces, made with care.</h1>
              <p className="lead mt-4 mb-4 hero-copy hero-banner-copy">Rooted in Balochi tradition, each piece carries the beauty of our culture, the skill of our hands, and the stories of generations.</p>
              <div className="d-flex flex-wrap gap-3">
                <Link className="btn btn-hero-primary btn-lg px-4" href="/shop">Shop the collection</Link>
                <a className="btn btn-outline-light btn-lg px-4" href="#heritage">Our heritage</a>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="container py-5 home-section" id="collection">
        <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
          <div>
            <p className="eyebrow mb-2">Selected pieces</p>
            <h2 className="section-title mb-0">Featured products</h2>
          </div>
          <Link className="text-link" href="/shop">View all products <span aria-hidden="true">&rarr;</span></Link>
        </div>

        <div className="row g-4">
          {featuredProducts.map((product) => (
            <div key={product.id} className="col-md-6 col-lg-4">
              <article className="product-card h-100">
                <Link className="product-image" href={`/product/${product.id}`}>
                  {product.imagePath ? <img src={product.imagePath} alt={product.name} /> : <span className="product-image-placeholder">[NEW WEBSITE NAME]</span>}
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
      </section>

      <section className="heritage-section" id="heritage">
        <div className="container py-5">
          <div className="row align-items-center g-5 py-lg-5">
            <div className="col-lg-5">
              <p className="eyebrow">A living tradition</p>
              <h2 className="section-title">Every fold carries a story.</h2>
            </div>
            <div className="col-lg-6 offset-lg-1">
              <p className="lead">The Dastkar is more than an accessory. It is a gesture of respect, a mark of belonging, and a practical piece of life across Balochistan.</p>
              <p className="text-secondary">We celebrate the patient hands, regional patterns, and quiet confidence behind every piece we select. Our collection brings that heritage into a considered, contemporary wardrobe.</p>
              <Link className="text-link" href="/about">Read our story <span aria-hidden="true">&rarr;</span></Link>
            </div>
          </div>
        </div>
      </section>

      <section className="container py-5 home-section">
        <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
          <div>
            <p className="eyebrow mb-2">The [NEW WEBSITE NAME] standard</p>
            <h2 className="section-title mb-0">Why choose us</h2>
          </div>
        </div>
        <div className="row g-4 mt-2">
          <div className="col-md-4">
            <div className="feature-item"><span className="feature-number">01</span><h3>Rooted in place</h3><p>Our edit honors the regional character of Balochi color, weave, and ornament.</p></div>
          </div>
          <div className="col-md-4">
            <div className="feature-item"><span className="feature-number">02</span><h3>Made to be worn</h3><p>Comfortable materials and considered proportions make tradition easy to live in.</p></div>
          </div>
          <div className="col-md-4">
            <div className="feature-item"><span className="feature-number">03</span><h3>Selected with care</h3><p>Each piece is reviewed for finish, feel, and the story it brings with it.</p></div>
          </div>
        </div>
      </section>

      <section className="container py-5 home-section">
        <div className="stats-strip row g-0 text-center">
          <div className="col-4"><strong>{stats.products}</strong><span>Pieces in edit</span></div>
          <div className="col-4"><strong>{stats.colors}</strong><span>Color choices</span></div>
          <div className="col-4"><strong>{stats.orders}</strong><span>Orders placed</span></div>
        </div>
      </section>

      <section className="quote-section">
        <div className="container py-5">
          <p className="eyebrow">From the community</p>
          <div id="testimonialCarousel" className="carousel slide" data-bs-ride="carousel">
            <div className="carousel-inner">
              <div className="carousel-item active">
                <blockquote>“The maroon border is even more beautiful in person. It feels like wearing something with a past, without feeling old-fashioned.”</blockquote>
                <p className="text-secondary mb-0">Amina R. &middot; Karachi</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="container py-5 home-section" id="faq">
        <div className="row g-5">
          <div className="col-lg-4">
            <p className="eyebrow">Good to know</p>
            <h2 className="section-title">Questions, answered.</h2>
            <Link className="text-link" href="/faq">Visit the FAQ <span aria-hidden="true">&rarr;</span></Link>
          </div>
          <div className="col-lg-7 offset-lg-1">
            <div className="faq-preview">
              <h3 className="h5">How do I choose a color?</h3>
              <p className="text-secondary">Each product page lists the available colors and their character so you can choose with confidence.</p>
              <h3 className="h5">Are the dastars ready to wear?</h3>
              <p className="text-secondary mb-0">Yes. We select pieces for a comfortable drape and include simple care guidance with every order.</p>
            </div>
          </div>
        </div>
      </section>

      <section className="newsletter-section">
        <div className="container py-5">
          <div className="row align-items-center g-4">
            <div className="col-lg-6">
              <p className="eyebrow">Stay close</p>
              <h2 className="section-title mb-2">Notes from the collection.</h2>
              <p className="text-secondary mb-0">New pieces, craft stories, and thoughtful styling, delivered occasionally.</p>
            </div>
            <div className="col-lg-5 offset-lg-1">
              <form className="newsletter-form" action="/newsletter-signup" method="post">
                <label className="visually-hidden" htmlFor="newsletterEmail">Email address</label>
                <input className="form-control" type="email" id="newsletterEmail" name="email" placeholder="Your email address" required />
                <button className="btn btn-dark" type="submit">Subscribe</button>
              </form>
            </div>
          </div>
        </div>
      </section>

      <section className="contact-cta">
        <div className="container py-5 text-center">
          <p className="eyebrow">Let’s talk</p>
          <h2 className="section-title">Looking for something particular?</h2>
          <p className="text-secondary mx-auto cta-copy">For gifting, styling questions, or a piece with a specific character, our team is happy to help.</p>
          <Link className="btn btn-dark px-4" href="/contact">Get in touch</Link>
        </div>
      </section>
      <Link className="sticky-cta" href="/shop">Explore [NEW WEBSITE NAME] <span aria-hidden="true">&rarr;</span></Link>
    </main>
  );
}
