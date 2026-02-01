import fs from "fs";
import path from "path";

/**
 * GET /api/reservations
 * Return mock reservations from mock/reservations.json
 */
export default function handler(req, res) {
  try {
    // Allow CORS (safe for demo; same-origin also works)
    res.setHeader("Access-Control-Allow-Origin", "*");
    res.setHeader("Access-Control-Allow-Methods", "GET, OPTIONS");
    res.setHeader("Access-Control-Allow-Headers", "Content-Type");

    // Handle preflight
    if (req.method === "OPTIONS") {
      return res.status(200).end();
    }

    if (req.method !== "GET") {
      return res.status(405).json({ ok: false, message: "Method Not Allowed" });
    }

    const filePath = path.join(process.cwd(), "mock", "reservations.json");
    const raw = fs.readFileSync(filePath, "utf-8");
    const reservations = JSON.parse(raw);

    // You can wrap it like a real API
    return res.status(200).json({
      ok: true,
      data: reservations,
      total: reservations.length,
    });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ ok: false, message: "Server Error" });
  }
}
