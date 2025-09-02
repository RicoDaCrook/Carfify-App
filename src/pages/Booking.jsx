import React, { useState, useEffect } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { useBooking } from '../contexts/BookingContext';

const Booking = () => {
  const { user } = useAuth();
  const { createBooking } = useBooking();
  const [services, setServices] = useState([]);
  const [selectedService, setSelectedService] = useState('');
  const [selectedDate, setSelectedDate] = useState('');
  const [selectedTime, setSelectedTime] = useState('');
  const [notes, setNotes] = useState('');
  const [loading, setLoading] = useState(false);
  const [availableTimes, setAvailableTimes] = useState([]);

  useEffect(() => {
    fetchServices();
  }, []);

  const fetchServices = async () => {
    try {
      const response = await fetch('/api/services/get.php');
      const data = await response.json();
      setServices(data);
    } catch (error) {
      console.error('Error fetching services:', error);
    }
  };

  const fetchAvailableTimes = async (date) => {
    if (!date) return;
    
    try {
      const response = await fetch(`/api/bookings/available-times.php?date=${date}`);
      const data = await response.json();
      setAvailableTimes(data.times);
    } catch (error) {
      console.error('Error fetching available times:', error);
    }
  };

  const handleDateChange = (date) => {
    setSelectedDate(date);
    fetchAvailableTimes(date);
    setSelectedTime('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const result = await createBooking({
        service_id: selectedService,
        date: selectedDate,
        time: selectedTime,
        notes,
        user_id: user.id
      });

      if (result.success) {
        alert('Buchung erfolgreich erstellt!');
        // Reset form
        setSelectedService('');
        setSelectedDate('');
        setSelectedTime('');
        setNotes('');
      } else {
        alert('Fehler: ' + result.error);
      }
    } catch (error) {
      alert('Buchung fehlgeschlagen');
    } finally {
      setLoading(false);
    }
  };

  const getMinDate = () => {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split('T')[0];
  };

  return (
    <div className="max-w-4xl mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold text-gray-900 mb-8">Termin Buchen</h1>
      
      <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow-lg p-6">
        <div className="mb-6">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Service Auswählen
          </label>
          <select
            value={selectedService}
            onChange={(e) => setSelectedService(e.target.value)}
            className="w-full border border-gray-300 rounded-md px-3 py-2"
            required
          >
            <option value="">Bitte wählen...</option>
            {services.map(service => (
              <option key={service.id} value={service.id}>
                {service.name} - €{service.price}
              </option>
            ))}
          </select>
        </div>

        <div className="grid md:grid-cols-2 gap-6 mb-6">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Datum
            </label>
            <input
              type="date"
              value={selectedDate}
              onChange={(e) => handleDateChange(e.target.value)}
              min={getMinDate()}
              className="w-full border border-gray-300 rounded-md px-3 py-2"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Uhrzeit
            </label>
            <select
              value={selectedTime}
              onChange={(e) => setSelectedTime(e.target.value)}
              className="w-full border border-gray-300 rounded-md px-3 py-2"
              required
              disabled={!selectedDate}
            >
              <option value="">Bitte wählen...</option>
              {availableTimes.map(time => (
                <option key={time} value={time}>{time}</option>
              ))}
            </select>
          </div>
        </div>

        <div className="mb-6">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Notizen (optional)
          </label>
          <textarea
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            rows={4}
            className="w-full border border-gray-300 rounded-md px-3 py-2"
            placeholder="Besondere Anforderungen oder Hinweise..."
          />
        </div>

        <button
          type="submit"
          disabled={loading || !selectedService || !selectedDate || !selectedTime}
          className="w-full bg-blue-600 text-white py-3 rounded-md hover:bg-blue-700 disabled:bg-gray-400"
        >
          {loading ? 'Wird gebucht...' : 'Termin Buchen'}
        </button>
      </form>
    </div>
  );
};

export default Booking;