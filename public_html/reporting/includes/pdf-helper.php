<?php
/**
 * Build report PDF with charts (via QuickChart), data tables, and comments.
 * Used by export-pdf.php and the save-as-PDF flow (table.php, saved-reports.php).
 */

function pdfStaticFeatureSupport(array $rows): array
{
    $features = ['cookiesEnabled', 'jsEnabled', 'imagesEnabled', 'cssEnabled'];
    $labels = ['Cookies', 'JavaScript', 'Images', 'CSS'];
    $total = count($rows) ?: 1;
    $values = [];
    foreach ($features as $f) {
        $count = 0;
        foreach ($rows as $r) {
            $p = is_string($r['payload']) ? json_decode($r['payload'], true) : $r['payload'];
            if (is_array($p) && !empty($p[$f])) {
                $count++;
            }
        }
        $values[] = (int) round(($count / $total) * 100);
    }
    return ['labels' => $labels, 'values' => $values];
}

function pdfPerformanceLoadTimeOverTime(array $rows): array
{
    $byDate = [];
    foreach ($rows as $r) {
        $p = is_string($r['payload']) ? json_decode($r['payload'], true) : $r['payload'];
        if (!is_array($p)) {
            continue;
        }
        $t = $p['totalLoadTime'] ?? null;
        if ($t === null && isset($p['loadEventEnd'], $p['startTime'])) {
            $t = $p['loadEventEnd'] - $p['startTime'];
        }
        if ($t === null && !empty($p['timingObject']) && isset($p['timingObject']['loadEventEnd'], $p['timingObject']['startTime'])) {
            $t = $p['timingObject']['loadEventEnd'] - $p['timingObject']['startTime'];
        }
        if ($t === null || empty($r['received_at'])) {
            continue;
        }
        $key = substr($r['received_at'], 0, 10);
        if (!isset($byDate[$key])) {
            $byDate[$key] = ['sum' => 0, 'n' => 0];
        }
        $byDate[$key]['sum'] += (float) $t;
        $byDate[$key]['n']++;
    }
    ksort($byDate);
    $labels = array_keys($byDate);
    $values = array_map(function ($d) {
        return (int) round($d['sum'] / $d['n']);
    }, $byDate);
    return ['labels' => $labels, 'values' => $values];
}

function pdfActivityIdleVsActive(array $rows): array
{
    $bySession = [];
    foreach ($rows as $r) {
        $sid = $r['session_id'] ?? (is_array($r['payload'] ?? null) ? ($r['payload']['sessionId'] ?? 'unknown') : 'unknown');
        if (!isset($bySession[$sid])) {
            $bySession[$sid] = ['idle' => 0, 'received' => []];
        }
        $p = is_string($r['payload']) ? json_decode($r['payload'], true) : ($r['payload'] ?? []);
        if (is_array($p) && isset($p['event'], $p['idleDuration']) && $p['event'] === 'idle_break') {
            $bySession[$sid]['idle'] += (float) $p['idleDuration'];
        }
        if (!empty($r['received_at'])) {
            $bySession[$sid]['received'][] = $r['received_at'];
        }
    }
    $labels = [];
    $activeData = [];
    $idleData = [];
    $slice = array_slice($bySession, 0, 12, true);
    foreach ($slice as $sid => $v) {
        $labels[] = strlen($sid) > 8 ? substr($sid, 0, 8) . '…' : $sid;
        $idleSec = ($v['idle'] / 1000) ?: 0;
        $activeSec = 0;
        if (count($v['received']) >= 2) {
            $times = array_filter(array_map(function ($d) {
                return strtotime($d);
            }, $v['received']));
            if (count($times) >= 2) {
                $activeSec = (max($times) - min($times)) - $idleSec;
            }
        }
        $activeData[] = round(max(0, $activeSec) * 10) / 10;
        $idleData[] = round($idleSec * 10) / 10;
    }
    return ['labels' => $labels, 'activeData' => $activeData, 'idleData' => $idleData];
}

function pdfFetchChartImage(array $chartConfig): ?string
{
    $url = 'https://quickchart.io/chart?width=520&height=260&c=' . urlencode(json_encode($chartConfig));
    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $img = @file_get_contents($url, false, $ctx);
    if ($img === false || strlen($img) < 100) {
        return null;
    }
    return 'data:image/png;base64,' . base64_encode($img);
}

function buildReportHtml(string $category, string $title, ?int $savedId, \PDO $pdo): string
{
    require_once __DIR__ . '/comments.php';
    $apiType = $category === 'behavioral' ? 'activity' : $category;
    $stmt = $pdo->prepare('SELECT id, received_at, session_id, payload FROM collector_log WHERE type = ? ORDER BY received_at DESC LIMIT 500');
    $stmt->execute([$apiType]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $comments = $savedId ? getReportComments($category, $savedId) : getReportComments($category);

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title>';
    $html .= '<style>body{font-family:system-ui,sans-serif;margin:1rem;color:#333;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#eee;} .comment{margin:1rem 0;padding:0.5rem;background:#f5f5f5;} pre{font-size:10px;overflow-x:auto;white-space:pre-wrap;} .chart-img{max-width:100%;height:auto;margin:1rem 0;} h2{margin-top:1.5rem;}</style></head><body>';
    $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
    $html .= '<p>Exported on ' . date('Y-m-d H:i:s') . ' — Category: ' . htmlspecialchars($category) . '</p>';

    // Charts section
    $html .= '<h2>Visual analytics</h2>';
    if ($category === 'static') {
        $feat = pdfStaticFeatureSupport($rows);
        if (!empty($feat['labels'])) {
            $chartConfig = [
                'type' => 'bar',
                'data' => [
                    'labels' => $feat['labels'],
                    'datasets' => [['label' => '% enabled', 'data' => $feat['values'], 'backgroundColor' => ['#6366f1','#8b5cf6','#a855f7','#818cf8']]]
                ],
                'options' => ['plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['max' => 100, 'title' => ['display' => true, 'text' => 'Percentage (%)']]]]
            ];
            $img = pdfFetchChartImage($chartConfig);
            if ($img) {
                $html .= '<p><strong>Browser feature support</strong></p><img class="chart-img" src="' . $img . '" alt="Feature support"/>';
            }
        }
    } elseif ($category === 'performance') {
        $lt = pdfPerformanceLoadTimeOverTime($rows);
        if (!empty($lt['labels'])) {
            $chartConfig = [
                'type' => 'line',
                'data' => [
                    'labels' => $lt['labels'],
                    'datasets' => [['label' => 'Load time (ms)', 'data' => $lt['values'], 'borderColor' => '#6366f1', 'fill' => true, 'tension' => 0.2]]
                ],
                'options' => ['plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['title' => ['display' => true, 'text' => 'Milliseconds (ms)']]]]
            ];
            $img = pdfFetchChartImage($chartConfig);
            if ($img) {
                $html .= '<p><strong>Average load time over time</strong></p><img class="chart-img" src="' . $img . '" alt="Load time"/>';
            }
        }
    } elseif ($category === 'behavioral') {
        $idleActive = pdfActivityIdleVsActive($rows);
        if (!empty($idleActive['labels'])) {
            $chartConfig = [
                'type' => 'bar',
                'data' => [
                    'labels' => $idleActive['labels'],
                    'datasets' => [
                        ['label' => 'Active (s)', 'data' => $idleActive['activeData'], 'backgroundColor' => '#6366f1'],
                        ['label' => 'Idle (s)', 'data' => $idleActive['idleData'], 'backgroundColor' => '#8b5cf6']
                    ]
                ],
                'options' => ['scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true]]]
            ];
            $img = pdfFetchChartImage($chartConfig);
            if ($img) {
                $html .= '<p><strong>Idle time vs active time</strong></p><img class="chart-img" src="' . $img . '" alt="Idle vs active"/>';
            }
        }
    }

    $html .= '<h2>Data table</h2><table><thead><tr><th>ID</th><th>Received</th><th>Session</th><th>Payload</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $pl = is_string($r['payload']) ? $r['payload'] : json_encode(json_decode($r['payload'], true) ?: []);
        $html .= '<tr><td>' . (int) $r['id'] . '</td><td>' . htmlspecialchars($r['received_at'] ?? '') . '</td><td>' . htmlspecialchars($r['session_id'] ?? '') . '</td><td><pre>' . htmlspecialchars($pl) . '</pre></td></tr>';
    }
    $html .= '</tbody></table>';
    $html .= '<h2>Section observations (analyst comments)</h2>';
    foreach ($comments as $c) {
        $html .= '<div class="comment"><small>' . htmlspecialchars($c['username'] . ' · ' . $c['created_at']) . '</small><p>' . nl2br(htmlspecialchars($c['comment_text'])) . '</p></div>';
    }
    if (empty($comments)) {
        $html .= '<p>No comments.</p>';
    }
    $html .= '</body></html>';
    return $html;
}

function buildReportPdf(string $category, string $title, ?int $savedId, \PDO $pdo): array
{
    $html = buildReportHtml($category, $title, $savedId, $pdo);
    $pdfBytes = null;
    if (is_file(__DIR__ . '/../vendor/autoload.php')) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfBytes = $dompdf->output();
        } catch (Throwable $e) {
            // leave pdfBytes null
        }
    }
    return ['html' => $html, 'pdf' => $pdfBytes];
}
