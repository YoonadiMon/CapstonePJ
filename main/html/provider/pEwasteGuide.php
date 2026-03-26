<?php
session_start();
include("../../php/dbConn.php");

$centres = [];
$centres_query = "SELECT centreID, name, address, state, postcode, contact, status FROM tblcentre WHERE status = 'Active' ORDER BY name";
$centres_result = $conn->query($centres_query);
if ($centres_result) {
    while ($row = $centres_result->fetch_assoc()) {
        $centres[] = $row;
    }
}

$items_with_points = [];
$items_query = "SELECT name, recycle_points FROM tblitem_type ORDER BY name";
$items_result = $conn->query($items_query);
if ($items_result) {
    while ($row = $items_result->fetch_assoc()) {
        $items_with_points[] = $row;
    }
}

$total_recycled = 0;
$weight_query = "SELECT SUM(weight) as total FROM tblitem WHERE status IN ('Collected', 'Received', 'Processed', 'Recycled')";
$weight_result = $conn->query($weight_query);
if ($weight_result && $row = $weight_result->fetch_assoc()) {
    $total_recycled = round($row['total'] ?? 0, 2);
}

$item_image_map = [
    'Extension Cord' => 'extension cord.jpg',
    'Adapters' => 'adapters.jpg',
    'Television' => 'television.jpg',
    'Projector' => 'projector.jpg',
    'Projectors' => 'projector.jpg',
    'Camera' => 'camera.jpg',
    'Scanner' => 'scanner.jpg',
    'Router' => 'router.jpg',
    'PC / CPU' => 'pc_cpu.jpg',
    'Modem' => 'modem.jpg',
    'Headphones / Earphones' => 'headphones_earphones.jpg',
    'Cables' => 'cables.jpg',
    'USB Flash Drive' => 'USB Flash Drive.jpg',
    'Power Bank' => 'powerbank.jpg',
    'Power Banks' => 'powerbank.jpg',
    'Laptop' => 'laptop.jpg',
    'External Hard Drive' => 'external-hard-disk.jpg'
];

function getItemImage($itemName, $itemImageMap) {
    return $itemImageMap[$itemName] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Waste Guide | AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        main {
            max-width: 1240px;
            margin: 0 auto;
            padding: 2rem 1.25rem 3rem;
        }

        .guide-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin: 0 0 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--BlueGray);
        }

        .guide-tab {
            padding: 0.85rem 1.4rem;
            background-color: var(--sec-bg-color);
            border-radius: 999px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
            color: var(--text-color);
            text-decoration: none;
            border: 1px solid var(--BlueGray);
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .guide-tab:hover {
            background-color: var(--LowMainBlue);
            border-color: var(--MainBlue);
        }

        .guide-tab.active {
            background-color: var(--MainBlue);
            color: white;
            border-color: var(--MainBlue);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .guide-content {
            display: none;
            animation: fadeIn 0.25s ease;
        }

        .guide-content.active {
            display: block;
        }

        .section-heading {
            margin-bottom: 1.5rem;
        }

        .section-heading h2 {
            font-size: clamp(2rem, 4vw, 2.6rem);
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.6rem;
            line-height: 1.2;
        }

        .section-heading p {
            font-size: 1.03rem;
            line-height: 1.7;
            color: var(--Gray);
            max-width: 820px;
            margin: 0;
        }

        .surface-card {
            background-color: var(--sec-bg-color);
            border: 1px solid rgba(128, 128, 128, 0.12);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .find-centre-container,
        .accepted-items-container,
        .preparation-container,
        .journey-container {
            padding: 0.5rem 0;
        }

        .search-section {
            width: 100%;
            margin-bottom: 1.5rem;
        }

        .search-bar {
            display: flex;
            width: 100%;
            max-width: 640px;
        }

        .search-bar input {
            flex: 1;
            padding: 1rem 1.2rem;
            border: 1px solid var(--BlueGray);
            border-radius: 999px 0 0 999px;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-size: 1rem;
            outline: none;
        }

        .search-bar input:focus,
        .search-items input:focus {
            border-color: var(--MainBlue);
            box-shadow: 0 0 0 3px rgba(52, 120, 246, 0.12);
        }

        .search-bar button {
            padding: 1rem 1.6rem;
            background-color: var(--MainBlue);
            color: white;
            border: none;
            border-radius: 0 999px 999px 0;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .search-bar button:hover,
        .download-btn:hover {
            background-color: var(--DarkerMainBlue);
            transform: translateY(-1px);
        }

        .search-feedback {
            margin-top: 0.9rem;
            font-size: 0.95rem;
            color: var(--Gray);
            min-height: 1.2rem;
        }

        .search-feedback.error {
            color: #d32f2f;
        }

        .search-feedback.success {
            color: #2e7d32;
        }

        .results-layout {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: 100%;
        }

        .centre-list,
        .centre-details {
            padding: 1.5rem;
        }

        .centre-list {
            flex: 1;
            max-height: 620px;
            overflow-y: auto;
        }

        .centre-list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--BlueGray);
        }

        .centre-list h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
        }

        .centre-count {
            background-color: var(--MainBlue);
            color: white;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            font-size: 0.86rem;
            font-weight: 600;
        }

        .centre-item {
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: 14px;
            margin-bottom: 0.75rem;
            border: 1px solid transparent;
            background-color: var(--bg-color);
        }

        .centre-item:hover {
            background-color: var(--LowMainBlue);
            border-color: rgba(52, 120, 246, 0.18);
        }

        .centre-item.active {
            background-color: var(--MainBlue);
            color: white;
            box-shadow: 0 10px 20px rgba(52, 120, 246, 0.22);
        }

        .centre-item.active .centre-address,
        .centre-item.active .centre-distance {
            color: rgba(255, 255, 255, 0.9);
        }

        .centre-name {
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 0.35rem;
        }

        .centre-address {
            font-size: 0.92rem;
            margin-bottom: 0.35rem;
            color: var(--Gray);
            line-height: 1.5;
        }

        .centre-distance {
            font-size: 0.84rem;
            color: var(--Gray);
            font-weight: 500;
        }

        .centre-details {
            flex: 1;
            min-height: 380px;
        }

        .detail-view {
            animation: fadeIn 0.25s ease;
        }

        .details-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .details-name {
            font-size: clamp(1.6rem, 3vw, 2rem);
            font-weight: 700;
            color: var(--text-color);
            line-height: 1.2;
        }

        .details-rating {
            background-color: var(--MainBlue);
            color: white;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .details-status {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background-color: rgba(76, 175, 80, 0.1);
            color: #2e7d32;
            font-weight: 700;
            margin: 0 0 1.2rem;
            padding: 0.5rem 0.9rem;
            border-radius: 999px;
        }

        .details-info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .details-info-card {
            background-color: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 16px;
            padding: 1rem 1.1rem;
        }

        .details-info-card h4 {
            margin: 0 0 0.7rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--MainBlue);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .details-info-card p,
        .details-phone a {
            margin: 0;
            color: var(--text-color);
            line-height: 1.65;
            text-decoration: none;
        }

        .hours-table {
            width: 100%;
            border-collapse: collapse;
        }

        .hours-table tr:not(:last-child) {
            border-bottom: 1px solid rgba(128, 128, 128, 0.12);
        }

        .hours-table td {
            padding: 0.55rem 0;
            vertical-align: top;
            color: var(--text-color);
        }

        .hours-table td:first-child {
            font-weight: 600;
            width: 120px;
        }

        .accepted-items-container h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--MainBlue);
            margin: 0 0 1rem;
        }

        .search-items {
            margin-bottom: 1.5rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .search-items input {
            flex: 1;
            padding: 0.95rem 1rem;
            border: 1px solid var(--BlueGray);
            border-radius: 999px;
            background-color: var(--bg-color);
            color: var(--text-color);
            min-width: 240px;
            outline: none;
        }

        .category-section {
            padding: 1.5rem;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            background-color: var(--sec-bg-color);
            border: 1px solid rgba(128, 128, 128, 0.12);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .item-card {
            background-color: var(--bg-color);
            padding: 0.95rem;
            border-radius: 18px;
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            border: 1px solid var(--BlueGray);
            cursor: pointer;
            min-height: 100%;
        }

        .item-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            border-color: rgba(52, 120, 246, 0.25);
        }

        .item-image-wrap {
            width: 100%;
            aspect-ratio: 16 / 10;
            border-radius: 14px;
            overflow: hidden;
            background-color: var(--sec-bg-color);
            border: 1px solid rgba(128, 128, 128, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .item-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .item-icon-fallback {
            font-size: 1.8rem;
            color: var(--MainBlue);
            width: 54px;
            height: 54px;
            min-width: 54px;
            border-radius: 16px;
            background-color: rgba(52, 120, 246, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .item-card-body {
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
            flex: 1;
        }

        .item-name {
            color: var(--text-color);
            font-size: 1rem;
            line-height: 1.45;
            font-weight: 700;
            min-height: 2.8em;
        }

        .item-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            align-items: center;
        }

        .item-points {
            font-size: 0.8rem;
            font-weight: 700;
            color: #2e7d32;
            background-color: rgba(76, 175, 80, 0.12);
            padding: 0.28rem 0.55rem;
            border-radius: 999px;
            white-space: nowrap;
        }

        .special-badge {
            background-color: #ff9800;
            color: white;
            font-size: 0.7rem;
            padding: 0.24rem 0.52rem;
            border-radius: 999px;
            white-space: nowrap;
            font-weight: 700;
        }

        .item-note {
            margin-top: 1rem;
            color: var(--Gray);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .no-items-message {
            display: none;
            padding: 1rem 1.2rem;
            border-radius: 14px;
            background-color: var(--sec-bg-color);
            border: 1px dashed var(--BlueGray);
            color: var(--Gray);
            font-weight: 500;
            margin-top: 1rem;
        }

        .item-detail-modal {
            display: none;
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }

        .item-detail-content {
            background-color: var(--sec-bg-color);
            padding: 2rem;
            border-radius: 20px;
            max-width: 420px;
            width: 100%;
            position: relative;
            border: 1px solid rgba(128, 128, 128, 0.15);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.16);
        }

        .item-detail-content h3 {
            margin: 0 0 0.75rem;
            font-size: 1.5rem;
            color: var(--text-color);
        }

        .item-detail-content p {
            color: var(--text-color);
            line-height: 1.65;
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            cursor: pointer;
            font-size: 1.5rem;
            color: var(--Gray);
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s ease;
        }

        .close-modal:hover {
            background-color: var(--bg-color);
        }

        .preparation-container h2,
        .journey-container h2 {
            font-size: clamp(2rem, 4vw, 2.6rem);
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 1rem;
        }

        .preparation-intro,
        .journey-intro {
            font-size: 1.05rem;
            line-height: 1.7;
            color: var(--Gray);
            margin-bottom: 2rem;
            max-width: 820px;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .step-card {
            display: flex;
            flex-direction: column;
            background: linear-gradient(180deg, var(--sec-bg-color) 0%, rgba(52, 120, 246, 0.03) 100%);
            border-radius: 24px;
            overflow: hidden;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            border: 1px solid rgba(128, 128, 128, 0.12);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.05);
            height: 100%;
        }

        .step-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 36px rgba(0, 0, 0, 0.08);
            border-color: rgba(52, 120, 246, 0.18);
        }

        .step-media {
            width: 100%;
            padding: 1rem 1rem 0;
        }

        .step-video {
            width: 100%;
            margin: 0;
            border-radius: 18px;
            overflow: hidden;
            background: #000;
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.12);
            aspect-ratio: 16 / 9;
        }

        .step-video video {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            border-radius: 18px;
            background: #000;
        }

        .video-caption {
            font-size: 0.78rem;
            color: var(--Gray);
            text-align: center;
            display: block;
            margin-top: 0.7rem;
            line-height: 1.5;
        }

        .step-body {
            display: flex;
            flex-direction: column;
            flex: 1;
            padding: 1.15rem 1.2rem 1.2rem;
        }

        .step-number {
            width: 44px;
            height: 44px;
            background-color: var(--MainBlue);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
            margin-right: 0.95rem;
            box-shadow: 0 8px 18px rgba(52, 120, 246, 0.28);
            flex-shrink: 0;
        }

        .step-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.95rem;
        }

        .step-title {
            font-size: 1.18rem;
            font-weight: 700;
            color: var(--text-color);
            line-height: 1.35;
        }

        .step-content {
            color: var(--text-color);
            line-height: 1.7;
            padding-left: 0;
            font-size: 0.97rem;
            flex: 1;
        }

        .step-icon {
            margin-right: 0.45rem;
            color: var(--MainBlue);
        }

        .checklist-item {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            margin-top: 1rem;
            cursor: pointer;
            padding: 0.9rem 1rem;
            border-radius: 14px;
            background-color: rgba(52, 120, 246, 0.05);
            border: 1px solid rgba(52, 120, 246, 0.09);
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .checklist-item:hover {
            background-color: rgba(52, 120, 246, 0.08);
            border-color: rgba(52, 120, 246, 0.14);
        }

        .checklist-item input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--MainBlue);
            margin-top: 0.12rem;
            flex-shrink: 0;
        }

        .checklist-item span {
            line-height: 1.5;
            color: var(--text-color);
            font-weight: 500;
        }

        .step-card.safety-card {
            background: linear-gradient(180deg, rgba(255, 152, 0, 0.08) 0%, var(--sec-bg-color) 100%);
            border-color: rgba(255, 152, 0, 0.22);
            min-height: 220px;
        }

        .step-card.safety-card .step-body {
            justify-content: center;
        }

        .download-btn {
            background-color: var(--MainBlue);
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .journey-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-bubble {
            background: linear-gradient(135deg, var(--MainBlue), var(--DarkerMainBlue));
            color: white;
            padding: 1.2rem 1rem;
            border-radius: 18px;
            text-align: center;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 12px 24px rgba(52, 120, 246, 0.18);
        }

        .stat-bubble .number {
            font-size: 1.9rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 0.35rem;
        }

        .stat-bubble .label {
            font-size: 0.88rem;
            opacity: 0.95;
            font-weight: 500;
        }

        .recycle-process-video {
            margin-bottom: 2rem;
            border-radius: 20px;
            padding: 1.5rem;
            background-color: var(--sec-bg-color);
            border: 1px solid rgba(128, 128, 128, 0.12);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        }

        .recycle-process-video h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--MainBlue);
            margin-bottom: 1rem;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 16px;
            margin-bottom: 0.5rem;
        }

        .video-container iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border-radius: 16px;
        }

        .video-description {
            font-size: 0.92rem;
            color: var(--Gray);
            text-align: center;
            margin-top: 0.6rem;
            line-height: 1.6;
        }

        .journey-steps {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .journey-step {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background-color: var(--sec-bg-color);
            border-radius: 20px;
            padding: 1.3rem 1.4rem;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid rgba(128, 128, 128, 0.12);
            position: relative;
            overflow: hidden;
        }

        .journey-step:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.06);
        }

        .journey-step::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            background: linear-gradient(to bottom, var(--MainBlue), var(--DarkerMainBlue));
        }

        .journey-step-content {
            flex: 1;
            padding-left: 0.6rem;
        }

        .journey-step h3 {
            font-size: 1.22rem;
            font-weight: 700;
            color: var(--MainBlue);
            margin-bottom: 0.7rem;
        }

        .journey-step p {
            color: var(--text-color);
            line-height: 1.65;
            margin: 0;
        }

        .extra-detail {
            margin-top: 0.9rem;
            padding: 0.95rem 1rem;
            background-color: var(--bg-color);
            border-radius: 14px;
            font-size: 0.94rem;
            line-height: 1.65;
            color: var(--text-color);
            border: 1px solid rgba(128, 128, 128, 0.12);
        }

        .impact-meter {
            background-color: var(--bg-color);
            border-radius: 16px;
            padding: 1.2rem;
            margin-top: 1.5rem;
            border: 1px solid var(--BlueGray);
        }

        .impact-meter h4 {
            margin: 0 0 0.55rem;
            color: var(--text-color);
            font-size: 1.1rem;
        }

        .impact-meter p {
            margin: 0;
            color: var(--Gray);
            line-height: 1.6;
        }

        .impact-bar {
            height: 10px;
            background-color: var(--BlueGray);
            border-radius: 999px;
            overflow: hidden;
            margin: 0.85rem 0 0.65rem;
        }

        .impact-fill {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, var(--MainBlue), var(--DarkerMainBlue));
            border-radius: 999px;
            transition: width 1s ease;
        }

        .impact-meta {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            font-size: 0.82rem;
            color: var(--text-color);
        }

        .impact-estimate {
            margin-top: 0.7rem;
            font-size: 0.82rem;
            color: var(--Gray);
        }

        .hidden {
            display: none !important;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (min-width: 760px) {
            .results-layout {
                flex-direction: row;
                align-items: flex-start;
            }

            .centre-list {
                max-width: 38%;
            }

            .centre-details {
                max-width: 62%;
            }

            .details-info-grid {
                grid-template-columns: 1fr 1fr;
            }

            .steps-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 1.6rem;
            }

            .step-card {
                min-height: 100%;
            }
            .step-card.safety-card {
                grid-column: 1 / -1;
                min-height: 180px;
            }
        }

        @media (max-width: 759px) {
            main {
                padding: 1.5rem 1rem 2.5rem;
            }

            .guide-tabs {
                gap: 0.6rem;
            }

            .guide-tab {
                width: 100%;
                justify-content: center;
            }

            .search-bar {
                flex-direction: column;
                gap: 0.75rem;
                max-width: 100%;
            }

            .search-bar input,
            .search-bar button {
                border-radius: 999px;
                width: 100%;
            }

            .items-grid {
                grid-template-columns: 1fr;
            }

            .step-media {
                padding: 0.9rem 0.9rem 0;
            }

            .step-body {
                padding: 1rem 1rem 1.05rem;
            }

            .step-title {
                font-size: 1.08rem;
            }

            .video-caption {
                font-size: 0.74rem;
            }
        }
    </style>
</head>
<body>
    <div id="cover" class="" onclick="hideMenu()"></div>
    
    <header>
        <section class="c-logo-section">
            <a href="../../html/provider/pHome.php" class="c-logo-link">
                <img src="../../assets/images/logo.png" alt="Logo" class="c-logo">
                <div class="c-text">AfterVolt</div>
            </a>
        </section>

        <nav class="c-navbar-side">
            <img src="../../assets/images/icon-menu.svg" alt="icon-menu" onclick="showMenu()" class="c-icon-btn" id="menuBtn">
            <div id="sidebarNav" class="c-navbar-side-menu">
                <img src="../../assets/images/icon-menu-close.svg" alt="icon-menu-close" onclick="hideMenu()" class="close-btn" id="closeBtn">
                <div class="c-navbar-side-items">
                    <section class="c-navbar-side-more">
                        <button id="themeToggleMobile" type="button">
                            <img src="../../assets/images/light-mode-icon.svg" alt="Light Mode Icon">
                        </button>
                        <a href="../../html/common/Setting.php">
                            <img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImgM">
                        </a>
                    </section>
                    <a href="../../html/provider/pHome.php">Home</a>
                    <a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a>
                    <a href="../../html/provider/pMainPickup.php">My Pickup</a>
                    <a href="../../html/provider/pEwasteGuide.php">E-waste Guide</a>
                    <a href="../../html/common/About.php">About</a>
                </div>
            </div>
        </nav>

        <nav class="c-navbar-desktop">
            <a href="../../html/provider/pHome.php">Home</a>
            <a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a>
            <a href="../../html/provider/pMainPickup.php">My Pickup</a>
            <a href="../../html/provider/pEwasteGuide.php">E-waste Guide</a>
            <a href="../../html/common/About.php">About</a>
        </nav>

        <section class="c-navbar-more">
            <button id="themeToggleDesktop" type="button">
                <img src="../../assets/images/light-mode-icon.svg" alt="Light Mode Icon">
            </button>
            <a href="../../html/common/Setting.php">
                <img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImg">
            </a>
        </section>
    </header>
    <hr>

    <main>
        <div class="guide-tabs">
            <a href="#" class="guide-tab active" onclick="switchTab(event, 'find-centre')">📍 <span>Find a Centre</span></a>
            <a href="#" class="guide-tab" onclick="switchTab(event, 'accepted-items')">📋 <span>Accepted Items</span></a>
            <a href="#" class="guide-tab" onclick="switchTab(event, 'preparation')">📝 <span>Preparation Guide</span></a>
            <a href="#" class="guide-tab" onclick="switchTab(event, 'journey')">🔄 <span>The Journey</span></a>
        </div>

        <div id="find-centre" class="guide-content active">
            <div class="find-centre-container">
                <div class="section-heading">
                    <h2>Find a Location Near You</h2>
                    <p>Browse our active collection centres and quickly search by centre name, address, state, or postcode.</p>
                </div>

                <div class="search-section">
                    <div class="search-bar">
                        <input type="text" placeholder="Enter centre name, address, state, or postcode" id="searchInput">
                        <button id="searchBtn" type="button">Search</button>
                    </div>
                    <div id="searchFeedback" class="search-feedback"></div>
                </div>

                <div class="results-layout">
                    <div class="centre-list surface-card">
                        <div class="centre-list-header">
                            <h3>All Locations</h3>
                            <span class="centre-count" id="totalCount"><?php echo count($centres); ?></span>
                        </div>

                        <div id="centreList">
                            <?php if (empty($centres)): ?>
                                <div class="centre-item">No centres available</div>
                            <?php else: ?>
                                <?php foreach ($centres as $index => $centre): ?>
                                    <div class="centre-item <?php echo $index === 0 ? 'active' : ''; ?>" data-id="<?php echo $centre['centreID']; ?>" onclick="selectCentre(<?php echo $centre['centreID']; ?>)">
                                        <div class="centre-name"><?php echo htmlspecialchars($centre['name']); ?></div>
                                        <div class="centre-address"><?php echo htmlspecialchars($centre['address']); ?></div>
                                        <div class="centre-distance"><?php echo htmlspecialchars($centre['postcode'] . ', ' . $centre['state']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="centre-details surface-card" id="centreDetails">
                        <?php if (!empty($centres)): ?>
                            <?php foreach ($centres as $index => $centre): ?>
                                <div id="detail-<?php echo $centre['centreID']; ?>" class="detail-view" style="<?php echo $index === 0 ? 'display: block;' : 'display: none;'; ?>">
                                    <div class="details-header">
                                        <span class="details-name"><?php echo htmlspecialchars($centre['name']); ?></span>
                                        <span class="details-rating">★★★★★</span>
                                    </div>

                                    <div class="details-status">
                                        <i class="fas fa-circle-check"></i>
                                        <?php echo $centre['status'] === 'Active' ? 'Open Now' : 'Closed'; ?>
                                    </div>

                                    <div class="details-info-grid">
                                        <div class="details-info-card">
                                            <h4><i class="fas fa-location-dot"></i> Address</h4>
                                            <p>
                                                <?php echo htmlspecialchars($centre['address']); ?><br>
                                                <?php echo htmlspecialchars($centre['postcode'] . ', ' . $centre['state']); ?>
                                            </p>
                                        </div>

                                        <div class="details-info-card">
                                            <h4><i class="fas fa-phone"></i> Contact</h4>
                                            <p class="details-phone">
                                                <a href="tel:<?php echo htmlspecialchars($centre['contact']); ?>">
                                                    <?php echo htmlspecialchars($centre['contact']); ?>
                                                </a>
                                            </p>
                                        </div>

                                        <div class="details-info-card" style="grid-column: 1 / -1;">
                                            <h4><i class="fas fa-clock"></i> Opening Hours</h4>
                                            <table class="hours-table">
                                                <tr><td>Monday</td><td>09:00 - 18:00</td></tr>
                                                <tr><td>Tuesday</td><td>09:00 - 18:00</td></tr>
                                                <tr><td>Wednesday</td><td>09:00 - 18:00</td></tr>
                                                <tr><td>Thursday</td><td>09:00 - 18:00</td></tr>
                                                <tr><td>Friday</td><td>09:00 - 18:00</td></tr>
                                                <tr><td>Saturday</td><td>10:00 - 16:00</td></tr>
                                                <tr><td>Sunday</td><td>Closed</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="detail-view">
                                <div class="section-heading" style="margin-bottom: 0;">
                                    <h2 style="font-size: 1.8rem;">No Active Centres</h2>
                                    <p>There are currently no active collection centres available.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="accepted-items" class="guide-content">
            <div class="accepted-items-container">
                <div class="section-heading">
                    <h2>What Can You Recycle?</h2>
                    <p>We accept a wide range of electronic devices. Search for an item below and click any card to learn more about handling and recycling guidance.</p>
                </div>

                <div class="search-items">
                    <input type="text" id="itemSearchInput" placeholder="Search for an item... (e.g., laptop, battery)">
                </div>

                <div id="noItemsMessage" class="no-items-message">No matching items found. Try another keyword.</div>

                <div class="category-section item-category">
                    <h3>📱 Small Appliances</h3>
                    <div class="items-grid">
                        <?php foreach ($items_with_points as $item): ?>
                            <?php if (in_array($item['name'], ['Smartphone', 'Tablet', 'Laptop', 'Digital camera', 'Projectors', 'MP3 Players', 'DVD Players', 'Power Bank', 'USB Flash Drive', 'External Hard Drive'])): ?>
                                <?php $itemImage = getItemImage($item['name'], $item_image_map); ?>
                                <div class="item-card" data-name="<?php echo strtolower($item['name']); ?>" onclick="showItemDetail('<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['recycle_points']; ?>)">
                                    <div class="item-image-wrap">
                                        <?php if ($itemImage): ?>
                                            <img class="item-img" src="../../assets/images/<?php echo rawurlencode($itemImage); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-microchip item-icon-fallback"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-card-body">
                                        <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                        <div class="item-meta">
                                            <span class="item-points">+<?php echo $item['recycle_points']; ?> pts</span>
                                            <?php if (in_array($item['name'], ['Power Bank'])): ?>
                                                <span class="special-badge">⚠️ Special</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="category-section item-category">
                    <h3>💻 Computer Hardware</h3>
                    <div class="items-grid">
                        <?php foreach ($items_with_points as $item): ?>
                            <?php if (in_array($item['name'], ['Keyboards', 'Desktop', 'Computer Mice', 'Monitors', 'Headphones / Earphones', 'Printers', 'Scanner', 'PC / CPU', 'Router', 'Modem', 'Cables'])): ?>
                                <?php $itemImage = getItemImage($item['name'], $item_image_map); ?>
                                <div class="item-card" data-name="<?php echo strtolower($item['name']); ?>" onclick="showItemDetail('<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['recycle_points']; ?>)">
                                    <div class="item-image-wrap">
                                        <?php if ($itemImage): ?>
                                            <img class="item-img" src="../../assets/images/<?php echo rawurlencode($itemImage); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-desktop item-icon-fallback"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-card-body">
                                        <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                        <div class="item-meta">
                                            <span class="item-points">+<?php echo $item['recycle_points']; ?> pts</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="category-section item-category">
                    <h3>📺 Entertainment & Audio</h3>
                    <div class="items-grid">
                        <?php foreach ($items_with_points as $item): ?>
                            <?php if (in_array($item['name'], ['Television', 'Speakers', 'Gaming Consoles', 'Camera', 'Projector'])): ?>
                                <?php $itemImage = getItemImage($item['name'], $item_image_map); ?>
                                <div class="item-card" data-name="<?php echo strtolower($item['name']); ?>" onclick="showItemDetail('<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['recycle_points']; ?>)">
                                    <div class="item-image-wrap">
                                        <?php if ($itemImage): ?>
                                            <img class="item-img" src="../../assets/images/<?php echo rawurlencode($itemImage); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-tv item-icon-fallback"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-card-body">
                                        <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                        <div class="item-meta">
                                            <span class="item-points">+<?php echo $item['recycle_points']; ?> pts</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="category-section item-category">
                    <h3>🔋 Batteries & Accessories</h3>
                    <div class="items-grid">
                        <?php foreach ($items_with_points as $item): ?>
                            <?php if (in_array($item['name'], ['AA/AAA Batteries', 'Phone Batteries', 'Laptop Batteries', 'Chargers & Cables', 'Power Banks', 'Adapters', 'Extension Cord'])): ?>
                                <?php $itemImage = getItemImage($item['name'], $item_image_map); ?>
                                <div class="item-card" data-name="<?php echo strtolower($item['name']); ?>" onclick="showItemDetail('<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['recycle_points']; ?>)">
                                    <div class="item-image-wrap">
                                        <?php if ($itemImage): ?>
                                            <img class="item-img" src="../../assets/images/<?php echo rawurlencode($itemImage); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-battery-full item-icon-fallback"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-card-body">
                                        <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                        <div class="item-meta">
                                            <span class="item-points">+<?php echo $item['recycle_points']; ?> pts</span>
                                            <span class="special-badge">⚠️ Special</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <p class="item-note">
                    <i class="fas fa-exclamation-triangle" style="color: orange;"></i>
                    Please tape the ends of lithium batteries before disposal.
                </p>
                </div>
                </div>

        <div id="preparation" class="guide-content">
            <div class="preparation-container">
                <h2>How to Prepare Your E-Waste</h2>
                <p class="preparation-intro">Follow these simple steps to ensure your e-waste is ready for collection or drop-off. Check items off as you complete them!</p>
                
                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-media">
                            <div class="step-video">
                                <video controls preload="metadata">
                                    <source src="../../assets/videos/step 1-backup data.mp4" type="video/mp4">
                                </video>
                            </div>
                            <span class="video-caption">📹 How to backup your phone data</span>
                        </div>
                        <div class="step-body">
                            <div class="step-header">
                                <div class="step-number">1</div>
                                <div class="step-title">Backup Your Data</div>
                            </div>
                            <div class="step-content">
                                <i class="fas fa-database step-icon"></i>
                                Before disposing of any device that stores information, back up your important files and photos.
                            </div>
                            <label class="checklist-item">
                                <input type="checkbox" class="step-checkbox" data-step="1"> <span>I have backed up my data</span>
                            </label>
                        </div>
                    </div>

                    <div class="step-card">
                        <div class="step-media">
                            <div class="step-video">
                                <video controls preload="metadata">
                                    <source src="../../assets/videos/step 2-wipe data.mp4" type="video/mp4">
                                </video>
                            </div>
                            <span class="video-caption">📹 How to factory reset your device</span>
                        </div>
                        <div class="step-body">
                            <div class="step-header">
                                <div class="step-number">2</div>
                                <div class="step-title">Wipe Personal Information</div>
                            </div>
                            <div class="step-content">
                                <i class="fas fa-user-shield step-icon"></i>
                                Perform a factory reset on phones and computers. Remove SIM and memory cards.
                            </div>
                            <label class="checklist-item">
                                <input type="checkbox" class="step-checkbox" data-step="2"> <span>I have wiped my data</span>
                            </label>
                        </div>
                    </div>

                    <div class="step-card">
                        <div class="step-media">
                            <div class="step-video">
                                <video controls preload="metadata">
                                    <source src="../../assets/videos/step 3-remove batteries.mp4" type="video/mp4">
                                </video>
                            </div>
                            <span class="video-caption">📹 How to safely remove batteries</span>
                        </div>
                        <div class="step-body">
                            <div class="step-header">
                                <div class="step-number">3</div>
                                <div class="step-title">Remove Batteries (If Possible)</div>
                            </div>
                            <div class="step-content">
                                <i class="fas fa-battery-half step-icon"></i>
                                Remove detachable batteries. For built-in batteries, leave them in place.
                            </div>
                            <label class="checklist-item">
                                <input type="checkbox" class="step-checkbox" data-step="3"> <span>Batteries removed if possible</span>
                            </label>
                        </div>
                    </div>

                    <div class="step-card">
                        <div class="step-media">
                            <div class="step-video">
                                <video controls preload="metadata">
                                    <source src="../../assets/videos/step 4-do not dismantle.mp4" type="video/mp4">
                                </video>
                            </div>
                            <span class="video-caption">📹 Why you should not dismantle devices</span>
                        </div>
                        <div class="step-body">
                            <div class="step-header">
                                <div class="step-number">4</div>
                                <div class="step-title">Do Not Dismantle</div>
                            </div>
                            <div class="step-content">
                                <i class="fas fa-tools step-icon"></i>
                                Leave devices intact. Professional recyclers have the right equipment.
                            </div>
                            <label class="checklist-item">
                                <input type="checkbox" class="step-checkbox" data-step="4"> <span>Device is intact</span>
                            </label>
                        </div>
                    </div>

                    <div class="step-card">
                        <div class="step-media">
                            <div class="step-video">
                                <video controls preload="metadata">
                                    <source src="../../assets/videos/step 5-pack securely.mp4" type="video/mp4">
                                </video>
                            </div>
                            <span class="video-caption">📹 How to pack e-waste safely</span>
                        </div>
                        <div class="step-body">
                            <div class="step-header">
                                <div class="step-number">5</div>
                                <div class="step-title">Pack Securely</div>
                            </div>
                            <div class="step-content">
                                <i class="fas fa-box step-icon"></i>
                                Place items in a box or bag. Use padding for fragile items.
                            </div>
                            <label class="checklist-item">
                                <input type="checkbox" class="step-checkbox" data-step="5"> <span>Items packed securely</span>
                            </label>
                        </div>
                    </div>

                    <div class="step-card safety-card">
                        <div class="step-body">
                            <div class="step-header">
                                <div class="step-number">⚠️</div>
                                <div class="step-title">Safety Tip</div>
                            </div>
                            <div class="step-content">
                                <i class="fas fa-exclamation-triangle step-icon" style="color: orange;"></i>
                                Tape lithium battery terminals with clear tape to prevent fire risk.
                            </div>
                            <label class="checklist-item">
                                <input type="checkbox" class="step-checkbox" data-step="safety"> <span>Battery terminals taped</span>
                            </label>
                        </div>
                    </div>
                </div>

                <button class="download-btn" type="button" onclick="downloadChecklist()">
                    <i class="fas fa-download"></i> Download Preparation Checklist
                </button>
            </div>
        </div>

        <div id="journey" class="guide-content">
            <div class="journey-container">
                <h2>What Happens to Your E-Waste?</h2>
                <p class="journey-intro">Ever wondered what happens after your e-waste is collected? Here is the journey from your doorstep to material recovery.</p>
                
                <div class="journey-stats">
                    <div class="stat-bubble">
                        <div class="number"><?php echo number_format($total_recycled, 2); ?> kg</div>
                        <div class="label">Total Recycled</div>
                    </div>
                    <div class="stat-bubble">
                        <div class="number" id="co2Saved">0</div>
                        <div class="label">Estimated CO₂ Saved (kg)</div>
                    </div>
                    <div class="stat-bubble">
                        <div class="number" id="waterSaved">0</div>
                        <div class="label">Estimated Water Saved (L)</div>
                    </div>
                </div>

                <div class="recycle-process-video">
                    <h3>♻️ Watch the E-Waste Recycling Process</h3>
                    <div class="video-container">
                        <iframe
                            src="https://www.youtube.com/embed/3s_ZNEFPiE0"
                            title="E-Waste Recycling Process"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen>
                        </iframe>
                    </div>
                    <p class="video-description">See how electronic devices are dismantled, sorted, and transformed into valuable materials for reuse.</p>
                </div>

                <div class="journey-steps">
                    <div class="journey-step" onclick="toggleStepDetail(this)">
                        <div class="journey-step-content">
                            <h3>📦 Step 1: Collection & Sorting</h3>
                            <p>E-waste arrives at the recycling facility and is sorted by type. Items that can still be reused are set aside for refurbishment.</p>
                        </div>
                    </div>

                    <div class="journey-step" onclick="toggleStepDetail(this)">
                        <div class="journey-step-content">
                            <h3>🔧 Step 2: Manual Disassembly</h3>
                            <p>Trained workers carefully take apart devices by hand, separating components like circuit boards, plastic casings, glass, metals, and batteries.</p>
                        </div>
                    </div>

                    <div class="journey-step" onclick="toggleStepDetail(this)">
                        <div class="journey-step-content">
                            <h3>⚙️ Step 3: Shredding & Separation</h3>
                            <p>Materials are shredded into small pieces. Magnets and other technologies separate ferrous metals, non-ferrous metals, and plastics.</p>
                        </div>
                    </div>

                    <div class="journey-step" onclick="toggleStepDetail(this)">
                        <div class="journey-step-content">
                            <h3>♻️ Step 4: Material Recovery</h3>
                            <p>Metals are melted down and reused. Plastics are processed into pellets. Precious metals like gold and silver are extracted from circuit boards.</p>
                        </div>
                    </div>

                    <div class="journey-step" onclick="toggleStepDetail(this)">
                        <div class="journey-step-content">
                            <h3>🏭 Step 5: Safe Disposal</h3>
                            <p>Hazardous materials that cannot be recycled are treated and disposed of safely according to environmental regulations.</p>
                        </div>
                    </div>
                </div>

                <div class="impact-meter">
                    <h4>🌍 Your Impact Matters</h4>
                    <p>Recycling 1,000 kg of e-waste can help reduce energy use, conserve water, and lower environmental harm.</p>
                    <div class="impact-bar">
                        <div class="impact-fill" id="impactFill"></div>
                    </div>
                    <div class="impact-meta">
                        <span>⚡ 1,500 kWh energy</span>
                        <span>💧 150,000 L water</span>
                        <span>🌲 50 trees</span>
                    </div>
                    <div class="impact-estimate">Estimated values are based on internal approximation for visual awareness.</div>
                </div>
            </div>
        </div>
    </main>

    <hr>

    <footer>
        <section class="c-footer-info-section">
            <a href="../../html/provider/pHome.php"><img src="../../assets/images/logo.png" alt="Logo" class="c-logo"></a>
            <div class="c-text">AfterVolt</div>
            <div class="c-text c-text-center">Promoting responsible e-waste collection and sustainable recycling practices in partnership with APU.</div>
            <div class="c-text c-text-label">+60 12 345 6789</div>
            <div class="c-text">abc@gmail.com</div>
        </section>
        <section class="c-footer-links-section">
            <div><b>Recycling</b><br><a href="../../html/provider/pEwasteGuide.php">E-Waste Guide</a></div>
            <div><b>My Activity</b><br><a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a><br><a href="../../html/provider/pMainPickup.php">My Pickup</a></div>
            <div><b>Proxy</b><br><a href="../../html/common/About.php">About</a><br><a href="../../html/common/Profile.php">Edit Profile</a><br><a href="../../html/common/Setting.php">Setting</a></div>
        </section>
    </footer>

    <div id="itemDetailModal" class="item-detail-modal">
        <div class="item-detail-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h3 id="modalItemName"></h3>
            <p id="modalItemPoints"></p>
            <p id="modalItemInfo"></p>
        </div>
    </div>

    <script src="../../javascript/mainScript.js"></script>
    <script>
        let centres = <?php echo json_encode($centres); ?>;

        const journeyDetails = {
            "📦 Step 1: Collection & Sorting": "Devices are first grouped by category so reusable items can be refurbished and damaged items can move to the correct recycling stream. Proper sorting reduces contamination and improves recovery efficiency.",
            "🔧 Step 2: Manual Disassembly": "Manual disassembly helps remove batteries, circuit boards, screens, and hazardous parts safely before machines process the remaining material. This step is important for worker safety and better material separation.",
            "⚙️ Step 3: Shredding & Separation": "After dismantling, machines break materials into smaller fragments. Different technologies then separate metals, plastics, and glass so each material can continue to the correct recovery stage.",
            "♻️ Step 4: Material Recovery": "Recovered materials are cleaned and processed for reuse in manufacturing. This helps reduce the need for new raw materials and supports a more circular electronics supply chain.",
            "🏭 Step 5: Safe Disposal": "Any hazardous residue that cannot be recovered is treated according to environmental requirements. Safe disposal prevents toxic substances from leaking into soil, air, and water."
        };

        function switchTab(event, tabId) {
            event.preventDefault();
            document.querySelectorAll('.guide-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.guide-content').forEach(content => content.classList.remove('active'));
            event.currentTarget.classList.add('active');
            document.getElementById(tabId).classList.add('active');

            if (tabId === 'journey') {
                setTimeout(updateImpactMetrics, 300);
            }
        }

        function selectCentre(centreId) {
            document.querySelectorAll('.centre-item').forEach(item => item.classList.remove('active'));
            const selectedItem = document.querySelector(`.centre-item[data-id="${centreId}"]`);
            if (selectedItem) selectedItem.classList.add('active');

            document.querySelectorAll('.detail-view').forEach(detail => detail.style.display = 'none');
            const selectedDetail = document.getElementById(`detail-${centreId}`);
            if (selectedDetail) selectedDetail.style.display = 'block';
        }

        function showItemDetail(name, points) {
            const modal = document.getElementById('itemDetailModal');
            document.getElementById('modalItemName').textContent = name;
            document.getElementById('modalItemPoints').innerHTML = `Earn <strong>${points} points</strong> for recycling this item!`;

            let info = '';
            if (name.includes('Battery') || name.includes('battery')) {
                info = '⚠️ Special handling required: Please tape battery terminals before disposal.';
            } else if (name.includes('Laptop') || name.includes('Computer')) {
                info = '💡 Tip: Remember to back up and wipe your data before recycling.';
            } else if (name.includes('Phone') || name.includes('Smartphone')) {
                info = '📱 Remove SIM card and memory card before recycling. Factory reset recommended.';
            } else {
                info = '♻️ This item can be recycled at any of our collection centres.';
            }

            document.getElementById('modalItemInfo').innerHTML = info;
            modal.style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('itemDetailModal').style.display = 'none';
        }

        function downloadChecklist() {
            let checklist = 'AfterVolt E-Waste Preparation Checklist\n\n';
            const steps = ['Backup Your Data', 'Wipe Personal Information', 'Remove Batteries (If Possible)', 'Do Not Dismantle', 'Pack Securely', 'Tape Battery Terminals'];
            checklist += '☐ ' + steps.join('\n☐ ');

            const blob = new Blob([checklist], { type: 'text/plain' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'ewaste_preparation_checklist.txt';
            link.click();
            URL.revokeObjectURL(link.href);
        }

        function updateImpactMetrics() {
            const totalWeight = <?php echo $total_recycled; ?>;
            const co2Saved = (totalWeight * 0.5).toFixed(1);
            const waterSaved = (totalWeight * 150).toFixed(0);
            document.getElementById('co2Saved').textContent = co2Saved;
            document.getElementById('waterSaved').textContent = waterSaved;

            const impactPercent = Math.min(100, (totalWeight / 1000) * 100);
            document.getElementById('impactFill').style.width = impactPercent + '%';
        }

        function toggleStepDetail(stepElement) {
            const content = stepElement.querySelector('.journey-step-content');
            const title = content.querySelector('h3')?.textContent.trim();
            const existing = content.querySelector('.extra-detail');

            document.querySelectorAll('.extra-detail').forEach(detail => {
                if (detail !== existing) detail.remove();
            });

            if (existing) {
                existing.remove();
                return;
            }

            const detail = document.createElement('div');
            detail.className = 'extra-detail';
            detail.innerHTML = journeyDetails[title] || 'This step supports safer, cleaner, and more effective recycling.';
            content.appendChild(detail);
        }

        function filterCentres() {
            const searchInput = document.getElementById('searchInput');
            const searchFeedback = document.getElementById('searchFeedback');
            const searchTerm = searchInput.value.trim().toLowerCase();

            if (!searchTerm) {
                searchFeedback.textContent = '';
                searchFeedback.className = 'search-feedback';

                const firstVisible = document.querySelector('.centre-item');
                if (firstVisible) {
                    const firstId = firstVisible.getAttribute('data-id');
                    selectCentre(firstId);
                }
                return;
            }

            let foundCentre = null;

            for (let i = 0; i < centres.length; i++) {
                const centre = centres[i];
                const name = (centre.name || '').toLowerCase();
                const address = (centre.address || '').toLowerCase();
                const state = (centre.state || '').toLowerCase();
                const postcode = (centre.postcode || '').toString().toLowerCase();

                if (
                    name.includes(searchTerm) ||
                    address.includes(searchTerm) ||
                    state.includes(searchTerm) ||
                    postcode.includes(searchTerm)
                ) {
                    foundCentre = centre;
                    break;
                }
            }

            if (foundCentre) {
                selectCentre(foundCentre.centreID);
                searchFeedback.textContent = `Showing result for "${searchInput.value.trim()}".`;
                searchFeedback.className = 'search-feedback success';
            } else {
                searchFeedback.textContent = 'No matching centre found.';
                searchFeedback.className = 'search-feedback error';
            }
        }

        function filterItems() {
            const itemSearch = document.getElementById('itemSearchInput');
            const searchTerm = itemSearch.value.trim().toLowerCase();
            const categories = document.querySelectorAll('.item-category');
            const noItemsMessage = document.getElementById('noItemsMessage');
            let totalVisibleItems = 0;

            categories.forEach(category => {
                const cards = category.querySelectorAll('.item-card');
                let visibleInCategory = 0;

                cards.forEach(card => {
                    const name = card.getAttribute('data-name') || '';
                    const isVisible = searchTerm === '' || name.includes(searchTerm);
                    card.style.display = isVisible ? 'flex' : 'none';
                    if (isVisible) visibleInCategory++;
                });

                category.style.display = visibleInCategory > 0 ? 'block' : 'none';
                totalVisibleItems += visibleInCategory;
            });

            noItemsMessage.style.display = totalVisibleItems === 0 ? 'block' : 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchBtn = document.getElementById('searchBtn');
            const searchInput = document.getElementById('searchInput');
            const itemSearch = document.getElementById('itemSearchInput');

            if (searchBtn) {
                searchBtn.addEventListener('click', filterCentres);
            }

            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') filterCentres();
                });
            }

            if (itemSearch) {
                itemSearch.addEventListener('input', filterItems);
            }

            const checkboxes = document.querySelectorAll('.step-checkbox');
            checkboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    const step = this.getAttribute('data-step');
                    if (this.checked) {
                        localStorage.setItem(`prep_step_${step}`, 'true');
                    } else {
                        localStorage.removeItem(`prep_step_${step}`);
                    }
                });

                const saved = localStorage.getItem(`prep_step_${cb.getAttribute('data-step')}`);
                if (saved === 'true') cb.checked = true;
            });

            updateImpactMetrics();
        });

        window.onclick = function(event) {
            const modal = document.getElementById('itemDetailModal');
            if (event.target === modal) closeModal();
        };
    </script>
</body>
</html>