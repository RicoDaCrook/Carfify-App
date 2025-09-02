import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

const Home = () => {
  const { user } = useAuth();

  const features = [
    {
      icon: 'fas fa-sparkles',
      title: 'Premium Innenreinigung',
      description: 'Tiefenreinigung aller Innenflächen mit hochwertigen Pflegemitteln'
    },
    {
      icon: 'fas fa-tint',
      title: 'Außenwäsche & Wachs',
      description: 'Handwäsche, Trocknung und Versiegelung für langanhaltenden Schutz'
    },
    {
      icon: 'fas fa-leaf',
      title: 'Ökologische Produkte',
      description: 'Umweltfreundliche Reinigungsmittel ohne schädliche Chemikalien'
    }
  ];

  const testimonials = [
    {
      name: 'Sarah M.',
      text: 'Mein Auto sieht aus wie neu! Der Service war schnell und professionell.',
      rating: 5
    },
    {
      name: 'Michael K.',
      text: 'Regelmäßiger Kunde - immer zufrieden mit der Qualität und dem Preis.',
      rating: 5
    }
  ];

  return (
    <div>
      {/* Hero Section */}
      <section className="relative bg-gradient-to-r from-blue-600 to-blue-800 text-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
          <div className="text-center">
            <h1 className="text-4xl md:text-6xl font-bold mb-6">
              Ihr Auto verdient das Beste
            </h1>
            <p className="text-xl md:text-2xl mb-8 opacity-90">
              Professionelle Autopflege mit Premium-Qualität und ökologischen Produkten
            </p>
            <div className="space-x-4">
              {user ? (
                <Link
                  to="/booking"
                  className="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition"
                >
                  Jetzt Buchen
                </Link>
              ) : (
                <>
                  <Link
                    to="/register"
                    className="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition"
                  >
                    Kostenlos Registrieren
                  </Link>
                  <Link
                    to="/services"
                    className="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition"
                  >
                    Services Ansehen
                  </Link>
                </>
              )}
            </div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Unsere Services
            </h2>
            <p className="text-xl text-gray-600">
              Alles rund um die professionelle Autopflege
            </p>
          </div>
          <div className="grid md:grid-cols-3 gap-8">
            {features.map((feature, index) => (
              <div key={index} className="bg-white p-8 rounded-lg shadow-lg">
                <div className="text-blue-600 text-4xl mb-4">
                  <i className={feature.icon}></i>
                </div>
                <h3 className="text-xl font-semibold mb-3">{feature.title}</h3>
                <p className="text-gray-600">{feature.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Pricing Section */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Transparente Preise
            </h2>
            <p className="text-xl text-gray-600">
              Keine versteckten Kosten - faire Preise für erstklassige Qualität
            </p>
          </div>
          <div className="grid md:grid-cols-3 gap-8">
            <div className="border rounded-lg p-8">
              <h3 className="text-2xl font-bold mb-4">Basic</h3>
              <p className="text-3xl font-bold text-blue-600 mb-4">€29</p>
              <ul className="space-y-2 text-gray-600">
                <li><i className="fas fa-check text-green-500 mr-2"></i>Außenwäsche</li>
                <li><i className="fas fa-check text-green-500 mr-2"></i>Innenreinigung</li>
                <li><i className="fas fa-check text-green-500 mr-2"></i>Fensterreinigung</li>
              </ul>
              <Link to={user ? "/booking" : "/register"} className="block mt-6 bg-blue-600 text-white text-center py-3 rounded-lg hover:bg-blue-700">
                Auswählen
              </Link>
            </div>
            <div className="border-2 border-blue-600 rounded-lg p-8 transform scale-105">
              <div className="bg-blue-600 text-white text-center py-1 px-4 rounded-full text-sm inline-block mb-4">
                Beliebteste
              </div>
              <h3 className="text-2xl font-bold mb-4">Premium</h3>
              <p className="text-3xl font-bold text-blue-600 mb-4">€59</p>
              <ul className="space-y-2 text-gray-600">
                <li><i className="fas fa-check text-green-500 mr-2"></i>Alles aus Basic</li>
                <li><i className="fas fa-check text-green-500 mr-2"></i>Wachsversiegelung</li>
                <li><i className="fas fa-check text-green-500 mr-2"></i>Lederpflege</li>
                <li><i className="fas fa-check text-green-500 mr-2"></i>Motorsäuberung</li>
              </ul>
              <Link to={user ? "/booking" : "/register"} className="block mt-6 bg-blue-600 text-white text-center py-3 rounded-lg hover:bg-blue-700">
                Auswählen
              </Link>
            </div>
            <div className="border rounded-lg p-8">
              <h3 className="text-2xl font-bold mb-4">Deluxe</h3>
              <p className="text-3xl font-bold text-blue-600 mb-4">€89</p>
              <ul className="space-y-2 text-gray-600">
                <li><i className="fas fa-check text-green-500 mr-2"></i>Alles aus Premium</li>
                <li><i className="fas fa-check text-green-500 mr-2"></i>Keramikversiegelung</li>
                <li><i className="fas fa-check text-green-500 mr-2"></i>Polsterreinigung</li>
                <li><i className="fas fa-check text-green-500 mr-2"></i>Reifenpflege</li>
              </ul>
              <Link to={user ? "/booking" : "/register"} className="block mt-6 bg-blue-600 text-white text-center py-3 rounded-lg hover:bg-blue-700">
                Auswählen
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* Testimonials Section */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Was unsere Kunden sagen
            </h2>
          </div>
          <div className="grid md:grid-cols-2 gap-8">
            {testimonials.map((testimonial, index) => (
              <div key={index} className="bg-white p-6 rounded-lg shadow-lg">
                <div className="flex mb-4">
                  {[...Array(testimonial.rating)].map((_, i) => (
                    <i key={i} className="fas fa-star text-yellow-400"></i>
                  ))}
                </div>
                <p className="text-gray-600 mb-4">"{testimonial.text}"</p>
                <p className="font-semibold">{testimonial.name}</p>
              </div>
            ))}
          </div>
        </div>
      </section>
    </div>
  );
};

export default Home;