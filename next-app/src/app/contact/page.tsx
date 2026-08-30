import Link from 'next/link';

export default function ContactPage() {
  return (
    <main>
      <section className="shop-intro">
        <div className="container py-5">
          <p className="eyebrow mb-2">Let’s talk</p>
          <h1 className="section-title mb-2">Contact [NEW WEBSITE NAME]</h1>
          <p className="text-secondary mb-0">Questions about a piece, an order, or a gift? Send us a note.</p>
        </div>
      </section>

      <div className="container py-5">
        <div className="row g-5">
          <div className="col-lg-5">
            <div className="border p-4">
              <p className="eyebrow">Reach us</p>
              <h2 className="h4">We’re happy to help.</h2>
              <p className="text-secondary">For gifting, styling questions, or custom requests, send us a few details and we’ll reply as soon as possible.</p>
              <p className="mb-2"><strong>Email:</strong> hello@newwebsite.com</p>
              <p className="mb-0"><strong>Location:</strong> Balochistan &amp; nationwide delivery</p>
            </div>
          </div>
          <div className="col-lg-7">
            <form className="bg-white border p-4" method="post" action="/contact">
              <div className="row g-3">
                <div className="col-md-6">
                  <label className="form-label" htmlFor="name">Name</label>
                  <input className="form-control" id="name" name="name" type="text" required />
                </div>
                <div className="col-md-6">
                  <label className="form-label" htmlFor="email">Email</label>
                  <input className="form-control" id="email" name="email" type="email" required />
                </div>
                <div className="col-md-6">
                  <label className="form-label" htmlFor="phone">Phone</label>
                  <input className="form-control" id="phone" name="phone" type="tel" />
                </div>
                <div className="col-md-6">
                  <label className="form-label" htmlFor="subject">Subject</label>
                  <input className="form-control" id="subject" name="subject" type="text" />
                </div>
                <div className="col-12">
                  <label className="form-label" htmlFor="message">Message</label>
                  <textarea className="form-control" id="message" name="message" rows={5} required />
                </div>
                <div className="col-12">
                  <button className="btn btn-dark" type="submit">Send message</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div className="container pb-5 text-center">
        <Link className="text-link" href="/faq">Visit the FAQ</Link>
      </div>
    </main>
  );
}
