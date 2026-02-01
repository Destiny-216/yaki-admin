import fs from "fs";
import path from "path";

/**
 * GET /api/shop
 * Return mock shop info from mock/shop.json
 */
export default function handler(req, res) {
  try {
    res.setHeader("Access-Control-Allow-Origin", "*");
    res.setHeader("Access-Control-Allow-Methods", "GET, OPTIONS");
    res.setHeader("Access-Control-Allow-Headers", "Content-Type");

    if (req.method === "OPTIONS") {
      return res.status(200).end();
    }

    if (req.method !== "GET") {
      return res.status(405).json({ ok: false, message: "Method Not Allowed" });
    }

    const filePath = path.join(process.cwd(), "mock", "shop.json");
    const raw = fs.readFileSync(filePath, "utf-8");
    const shop = JSON.parse(raw);

    return res.status(200).json({ ok: true, data: shop });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ ok: false, message: "Server Error" });
  }
}
