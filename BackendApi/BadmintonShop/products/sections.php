<?php
require_once __DIR__ . '/../bootstrap.php';

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'GET') respond(['error'=>'method_not_allowed'],405);

  // Coming soon = stock = 0
  $coming = $mysqli->query("
    SELECT p.productID, p.productName, p.price, COALESCE(pi.imageUrl,'') imageUrl
    FROM products p LEFT JOIN productimages pi ON pi.productID=p.productID
    WHERE p.stock=0
    ORDER BY p.productID DESC LIMIT 10
  ")->fetch_all(MYSQLI_ASSOC);

  // New arrivals = sort by createdDate desc
  $newArrivals = $mysqli->query("
    SELECT p.productID, p.productName, p.price, COALESCE(pi.imageUrl,'') imageUrl
    FROM products p LEFT JOIN productimages pi ON pi.productID=p.productID
    ORDER BY datetime(p.createdDate) DESC
    LIMIT 10
  ")->fetch_all(MYSQLI_ASSOC);

  // Best selling = từ orderdetails aggregate
  $bestSelling = $mysqli->query("
    SELECT p.productID, p.productName, p.price, COALESCE(pi.imageUrl,'') imageUrl,
           SUM(od.quantity) as sold
    FROM orderdetails od
    JOIN products p ON p.productID=od.productID
    LEFT JOIN productimages pi ON pi.productID=p.productID
    GROUP BY p.productID
    ORDER BY sold DESC
    LIMIT 10
  ")->fetch_all(MYSQLI_ASSOC);

  // Featured = đang có promotions
  $featured = $mysqli->query("
    SELECT DISTINCT p.productID, p.productName, p.price, COALESCE(pi.imageUrl,'') imageUrl
    FROM promotionproducts pp
    JOIN promotions pr ON pr.promoID=pp.promoID
    JOIN products p ON p.productID=pp.productID
    LEFT JOIN productimages pi ON pi.productID=p.productID
    WHERE date(pr.startDate) <= date('now') AND date(pr.endDate) >= date('now')
    ORDER BY p.productID DESC
    LIMIT 10
  ")->fetch_all(MYSQLI_ASSOC);

  respond([
    'comingSoon'   => $coming,
    'newArrivals'  => $newArrivals,
    'bestSelling'  => $bestSelling,
    'featured'     => $featured
  ]);
} catch (Throwable $e) {
  respond(['error'=>'server_error'],500);
}
