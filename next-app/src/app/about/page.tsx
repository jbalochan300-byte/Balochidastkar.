import Link from 'next/link';

export default function AboutPage() {
  return (
    <main>
      <section className="shop-intro">
        <div className="container py-5">
          <p className="eyebrow mb-2">Our story</p>
          <h1 className="section-title mb-2">Craft, culture, and careful living.</h1>
          <p className="text-secondary mb-0">[NEW WEBSITE NAME] brings together heritage, practical elegance, and the confidence of a piece made to be worn and remembered.</p>
        </div>
      </section>

      <div className="container py-5">
        <div className="row g-5 align-items-center">
          <div className="col-lg-6">
            <p className="eyebrow">A living tradition</p>
            <h2 className="section-title">Every fold carries a story.</h2>
            <p className="text-secondary">From coastal Makran to the highland drifts of Balochistan, cloth and color speak with a specific rhythm. Our edit celebrates the hands and homes that keep these traditions alive.</p>
            <p className="text-secondary">We choose pieces that feel both rooted in place and easy to live with — useful, expressive, and thoughtful in everyday life.</p>
          </div>
          <div className="col-lg-6">
            <div className="product-image" style={{ minHeight: 420 }}>
              <img src="https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=1400&q=80" alt="Balochi textile artistry" />
            </div>
          </div>
        </div>
      </div>

      <section className="heritage-section">
        <div className="container py-5">
          <div className="row g-4">
            <div className="col-md-4"><div className="feature-item"><span className="feature-number">01</span><h3>Rooted in place</h3><p>Our edit honors the regional character of Balochi cloth, color, and ornament.</p></div></div>
            <div className="col-md-4"><div className="feature-item"><span className="feature-number">02</span><h3>Made to be worn</h3><p>Comfort and practicality are part of the design, not an afterthought.</p></div></div>
            <div className="col-md-4"><div className="feature-item"><span className="feature-number">03</span><h3>Selected with care</h3><p>We review every piece for finish, story, and everyday value.</p></div></div>
          </div>
        </div>
      </section>

      <div className="container py-5 text-center">
        <p className="eyebrow">Curious to browse?</p>
        <Link className="btn btn-dark px-4" href="/shop">Shop the collection</Link>
      </div>
    </main>
  );
}
