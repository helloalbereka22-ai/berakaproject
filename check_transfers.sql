-- Check today's transfers
SELECT 
    DATE(moved_at) as moved_date,
    COUNT(*) as total_count,
    COUNT(CASE WHEN reference_type = 'StockTransfer' THEN 1 END) as stock_transfer_count,
    COUNT(CASE WHEN reference_type = 'transfer' THEN 1 END) as transfer_count,
    COUNT(CASE WHEN reference_type IS NULL THEN 1 END) as null_ref_count
FROM stock_movements 
WHERE direction = 'in' 
  AND DATE(moved_at) = CURDATE()
GROUP BY DATE(moved_at);

-- Show all reference types in today's movements
SELECT DISTINCT reference_type FROM stock_movements 
WHERE DATE(moved_at) = CURDATE() 
ORDER BY reference_type;

-- Show sample movements from today with direction='in'
SELECT id, movement_type, reference_type, direction, quantity, moved_at
FROM stock_movements
WHERE DATE(moved_at) = CURDATE() AND direction = 'in'
ORDER BY moved_at DESC
LIMIT 10;
