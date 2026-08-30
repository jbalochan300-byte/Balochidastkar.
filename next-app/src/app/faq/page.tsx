import Link from 'next/link';

const faqs = [
  {
    question: 'How do I choose a color?',
    answer: 'Each product page lists the available colors and their look, so you can choose what suits your wardrobe and occasion.',
  },
  {
    question: 'Are the dastars ready to wear?',
    answer: 'Yes. We select pieces for comfort and drape, and they are ready to use as part of your everyday or special styling.',
  },
  {
    question: 'Do you offer gift-ready packaging?',
    answer: 'We can help with gifting and styling questions, and our team is happy to advise on the best piece for your occasion.',
  },
  {
    question: 'How long does shipping take?',
    answer: 'Most orders are prepared promptly. We recommend contacting the team directly for special timing or gifting requests.',
  },
];

export default function FaqPage() {
  return (
    <main>
      <section className="shop-intro">
        <div className="container py-5">
          <p className="eyebrow mb-2">Help desk</p>
          <h1 className="section-title mb-2">Take your time.</h1>
          <p className="text-secondary mb-0">Still curious? <Link className="text-link" href="/contact">Send us a note.</Link></p>
        </div>
      </section>

      <div className="container py-5">
        <div className="accordion" style={{ display: 'grid', gap: '1rem' }}>
          {faqs.map((item) => (
            <div key={item.question} className="border p-4">
              <h3 className="h5 mb-3">{item.question}</h3>
              <p className="text-secondary mb-0">{item.answer}</p>
            </div>
          ))}
        </div>
      </div>
    </main>
  );
}
