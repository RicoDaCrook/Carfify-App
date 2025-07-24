export default function handler(req, res) {
  res.status(200).json({
    status: 'ok',
    message: 'Vercel API funktioniert!',
    timestamp: new Date().toISOString()
  });
}
