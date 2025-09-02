import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';

const Navbar = () => {
  const [isOpen, setIsOpen] = useState(false);
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate('/');
  };

  return (
    <nav className="bg-white shadow-lg">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16">
          <div className="flex items-center">
            <Link to="/" className="flex items-center space-x-2">
              <i className="fas fa-car text-blue-600 text-2xl"></i>
              <span className="font-bold text-xl text-gray-900">Carfify</span>
            </Link>
          </div>

          <div className="hidden md:flex items-center space-x-8">
            <Link to="/" className="text-gray-700 hover:text-blue-600 px-3 py-2">Start</Link>
            <Link to="/services" className="text-gray-700 hover:text-blue-600 px-3 py-2">Services</Link>
            {user ? (
              <>
                <Link to="/booking" className="text-gray-700 hover:text-blue-600 px-3 py-2">Buchen</Link>
                <Link to="/dashboard" className="text-gray-700 hover:text-blue-600 px-3 py-2">Dashboard</Link>
                <button onClick={handleLogout} className="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                  Abmelden
                </button>
              </>
            ) : (
              <div className="flex space-x-4">
                <Link to="/login" className="text-gray-700 hover:text-blue-600 px-3 py-2">Anmelden</Link>
                <Link to="/register" className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                  Registrieren
                </Link>
              </div>
            )}
          </div>

          <div className="md:hidden flex items-center">
            <button onClick={() => setIsOpen(!isOpen)} className="text-gray-700">
              <i className={`fas ${isOpen ? 'fa-times' : 'fa-bars'} text-xl`}></i>
            </button>
          </div>
        </div>
      </div>

      {isOpen && (
        <div className="md:hidden">
          <div className="px-2 pt-2 pb-3 space-y-1">
            <Link to="/" className="block px-3 py-2 text-gray-700">Start</Link>
            <Link to="/services" className="block px-3 py-2 text-gray-700">Services</Link>
            {user ? (
              <>
                <Link to="/booking" className="block px-3 py-2 text-gray-700">Buchen</Link>
                <Link to="/dashboard" className="block px-3 py-2 text-gray-700">Dashboard</Link>
                <button onClick={handleLogout} className="block w-full text-left px-3 py-2 text-red-600">
                  Abmelden
                </button>
              </>
            ) : (
              <>
                <Link to="/login" className="block px-3 py-2 text-gray-700">Anmelden</Link>
                <Link to="/register" className="block px-3 py-2 text-blue-600">Registrieren</Link>
              </>
            )}
          </div>
        </div>
      )}
    </nav>
  );
};

export default Navbar;