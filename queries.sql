-- rekap jam pemakaian ruangan (bulan berjalan, status CONFIRMED)
SELECT
    r.id AS room_id,
    r.name,
    COALESCE(
        ROUND(
            SUM(EXTRACT(EPOCH FROM (b.end_time - b.start_time)) / 3600.0)::NUMERIC,
            2
        ),
        0
    ) AS total_hours
FROM rooms r
LEFT JOIN bookings b
    ON b.room_id = r.id
    AND b.status = 'CONFIRMED'
    AND b.start_time >= date_trunc('month', CURRENT_TIMESTAMP)
    AND b.start_time < date_trunc('month', CURRENT_TIMESTAMP) + INTERVAL '1 month'
GROUP BY r.id, r.name
ORDER BY r.id;

-- pencarian ruangan tersedia
SELECT
    r.id,
    r.name,
    r.capacity
FROM rooms r
WHERE r.is_active = TRUE
  AND NOT EXISTS (
      SELECT 1
      FROM bookings b
      WHERE b.room_id = r.id
        AND b.status = 'CONFIRMED'
        AND b.start_time < TIMESTAMPTZ '2026-12-01 12:00:00+00'
        AND b.end_time > TIMESTAMPTZ '2026-12-01 10:00:00+00'
  )
ORDER BY r.id;