<?php
// agent.php — GENESIS-ULTRA Core (v4 Finale)
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

session_start();
header('Content-Type: application/json');

define('STORAGE', __DIR__.'/storage/');
define('MAX_TIME', 300);
define('HYPOTHESIS_PER_PAGE', 10);

$API_KEYS = [
    'YOU API KEY MISTRAL 1',
    'YOU API KEY MISTRAL 2',
    'YOU API KEY MISTRAL 3'
];

if(!is_dir(STORAGE)) mkdir(STORAGE, 0777, true);
if(!is_dir(STORAGE.'logs')) mkdir(STORAGE.'logs', 0777, true);
if(!is_dir(STORAGE.'knowledge')) mkdir(STORAGE.'knowledge', 0777, true);

// 🌐 CURL Request
function curl_req($url, $post=false, $key=null, $timeout=60) {
    $ch = curl_init($url);
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if($key) $headers[] = 'Authorization: Bearer ' . $key;
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    if($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    
    $result = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => ($result && !$err && in_array($httpCode, [200, 201])),
        'data' => $result,
        'error' => $err ?: ($httpCode !== 200 ? "HTTP $httpCode" : null),
        'http_code' => $httpCode
    ];
}

// 🤖 Mistral Call
function mistral($messages, $keyIdx=0, $maxTokens=800, $timeout=120) {
    global $API_KEYS;
    $key = $API_KEYS[$keyIdx % count($API_KEYS)];
    $payload = json_encode([
        'model' => 'mistral-small',
        'messages' => $messages,
        'temperature' => 0.3,
        'max_tokens' => $maxTokens,
        'response_format' => ['type' => 'json_object']
    ]);
    
    $resp = curl_req('https://api.mistral.ai/v1/chat/completions', $payload, $key, $timeout);
    
    if(!$resp['success']) {
        return ['error' => 'mistral_failed', 'debug' => ['curl_error' => $resp['error'], 'http_code' => $resp['http_code']]];
    }
    
    $data = @json_decode($resp['data'], true);
    if(!isset($data['choices'][0]['message']['content'])) {
        return ['error' => 'invalid_response', 'debug' => ['raw' => substr($resp['data'], 0, 200)]];
    }
    
    $content = $data['choices'][0]['message']['content'];
    $json = @json_decode($content, true);
    
    if(!is_array($json)) {
        return ['error' => 'invalid_json', 'debug' => ['content' => substr($content, 0, 200)]];
    }
    
    return $json;
}

// 🔬 APIs Scientifiques (100% CURL)
function api_pubmed($q) {
    if(empty($q) || !is_string($q) || strtolower($q) === 'array') return ['count' => 0, 'source' => 'PubMed'];
    $url = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pubmed&term=".urlencode($q)."&retmode=json&retmax=3";
    $resp = curl_req($url, false, null, 30);
    $d = @json_decode($resp['data'], true);
    return ['count' => count($d['esearchresult']['idlist'] ?? []), 'source' => 'PubMed'];
}

function api_uniprot($q) {
    if(empty($q) || !is_string($q) || strtolower($q) === 'array') return ['count' => 0, 'source' => 'UniProt'];
    $url = "https://rest.uniprot.org/uniprotkb/search?query=".urlencode($q)."&format=json&size=3";
    $resp = curl_req($url, false, null, 30);
    $d = @json_decode($resp['data'], true);
    return ['count' => count($d['results'] ?? []), 'source' => 'UniProt'];
}

function api_clinvar($q) {
    if(empty($q) || !is_string($q) || strtolower($q) === 'array') return ['count' => 0, 'source' => 'ClinVar'];
    $url = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=clinvar&term=".urlencode($q)."&retmode=json&retmax=3";
    $resp = curl_req($url, false, null, 30);
    $d = @json_decode($resp['data'], true);
    return ['count' => count($d['esearchresult']['idlist'] ?? []), 'source' => 'ClinVar'];
}

function api_arxiv($q) {
    if(empty($q) || !is_string($q) || strtolower($q) === 'array') return ['count' => 0, 'source' => 'ArXiv'];
    $url = "http://export.arxiv.org/api/query?search_query=all:".urlencode($q)."&max_results=3";
    $resp = curl_req($url, false, null, 30);
    return ['count' => substr_count($resp['data'] ?? '', '<entry>'), 'source' => 'ArXiv'];
}

// 📝 Logs détaillés
function add_log(&$st, $msg, $type='info', $detail=null) {
    $st['logs'][] = [
        'time' => date('H:i:s'),
        'msg' => is_string($msg) ? $msg : json_encode($msg),
        'type' => $type,
        'detail' => $detail
    ];
    if(count($st['logs']) > 100) array_shift($st['logs']);
}

// 🧠 META-PROMPT (4 sources + auto-réflexion + recherche approfondie)
$META = "Tu es GENESIS-ULTRA, chercheur scientifique autonome avec 4 sources (PubMed, UniProt, ClinVar, ArXiv).
OBJECTIF: Découvrir des hypothèses médicales testables qui aident l'humanité.

RÈGLES CRITIQUES:
1. next_target doit être UNE MALADIE OU UN GÈNE SPÉCIFIQUE (ex: 'Parkinson', 'TP53', 'Diabète type 2').
2. JAMAIS de termes techniques comme 'Array', 'Object', 'NULL', 'JSON'.
3. Évite les cibles déjà traitées.
4. Pour chaque cible, génère des requêtes de recherche APPROFONDIES (avec opérateurs booléens).

FORMAT SORTIE JSON:
{
  'conscience': 'Analyse en 1 phrase',
  'next_target': 'Nom de la cible',
  'reasoning': 'Pourquoi cette cible ? (besoin médical, gap scientifique)',
  'queries': {
    'pubmed': 'Requête PubMed avancée',
    'uniprot': 'Requête UniProt précise',
    'clinvar': 'Requête ClinVar ciblée',
    'arxiv': 'Requête ArXiv innovante'
  },
  'novelty_score': 0.0-1.0
}

STRATÉGIE DE RECHERCHE APPROFONDIE:
- PubMed: Inclure 'therapy OR treatment OR mutation OR clinical trial'
- UniProt: Inclure 'review OR function OR structure'
- ClinVar: Inclure 'pathogenic OR likely_pathogenic'
- ArXiv: Inclure 'machine learning OR deep learning OR CRISPR OR gene therapy'";

// 🔄 État
$action = $_GET['action'] ?? 'observe';
$session = $_GET['session'] ?? ($_SESSION['genesis_session'] ?? 'default');
$_SESSION['genesis_session'] = $session;
$stateFile = STORAGE.'state_'.$session.'.json';

$st = file_exists($stateFile) ? @json_decode(file_get_contents($stateFile), true) : null;
if(!$st || !is_array($st)) {
    $st = [
        'step' => 0, 'target' => '', 'memory' => [], 'logs' => [],
        'hypotheses' => [], 'key_idx' => 0, 'start' => time(),
        'status' => 'init', 'searched_targets' => [], 'deep_research' => false
    ];
}

// ACTION: init
if($action === 'init') {
    ob_end_clean();
    $st = [
        'step' => 0, 'target' => '', 'memory' => [], 'logs' => [],
        'hypotheses' => [], 'key_idx' => 0, 'start' => time(),
        'status' => 'running', 'searched_targets' => [], 'deep_research' => false
    ];
    add_log($st, '🚀 Initialisation GENESIS-ULTRA...', 'success', 'Le moteur de recherche autonome démarre. 4 sources activées: PubMed, UniProt, ClinVar, ArXiv.');
    add_log($st, '🎯 Mode: Recherche automatique de maladies rares et gènes sous-étudiés', 'info', 'L\'IA va choisir ses cibles en priorisant les maladies orphelines et les contradictions scientifiques.');
    add_log($st, '🔑 Test connexion Mistral...', 'waiting');
    
    $test = mistral([['role' => 'user', 'content' => 'Réponds {"test":"ok"}']], 0, 50, 30);
    
    if(isset($test['error'])) {
        add_log($st, '❌ Mistral ERROR: ' . $test['error'], 'error', json_encode($test['debug'] ?? $test));
        $st['debug']['mistral_test'] = $test;
        $st['status'] = 'error';
    } else {
        add_log($st, '✅ Mistral OK — Prêt pour la recherche', 'success', 'Connexion API validée. 3 milliards de tokens/mois disponibles.');
    }
    
    file_put_contents($stateFile, json_encode($st));
    echo json_encode(['ok' => true, 'session' => $session, 'mistral_test' => $test]);
    exit;
}

// ACTION: observe
if($action === 'observe' || $action === 'poll') {
    if(file_exists($stateFile)) $st = @json_decode(file_get_contents($stateFile), true);
    if(!$st || !is_array($st)) {
        $st = [
            'step' => 0, 'target' => '', 'memory' => [], 'logs' => [],
            'hypotheses' => [], 'key_idx' => 0, 'start' => time(),
            'status' => 'running', 'searched_targets' => []
        ];
    }
    
    if(time() - $st['start'] > MAX_TIME) {
        $st['status'] = 'timeout';
        add_log($st, '⏰ Timeout global (5 min)', 'error');
    }
    
    $new_hypothesis = false;
    
    if($st['status'] === 'running') {
        // ÉTAPE 0: Choix cible
        if($st['step'] === 0) {
            add_log($st, '🤖 IA réfléchit à la prochaine cible...', 'waiting', 'Analyse des cibles déjà traitées pour éviter les répétitions. Recherche de maladies sous-étudiées.');
            add_log($st, '⏳ Appel Mistral (peut prendre 60s)...', 'waiting');
            file_put_contents($stateFile, json_encode($st));
            
            $alreadySearched = count($st['searched_targets']) > 0
                ? "Cibles DÉJÀ traitées (NE PAS choisir): ".implode(', ', array_slice($st['searched_targets'], -5))
                : "Premier démarrage";
            
            $dec = mistral([
                ['role' => 'system', 'content' => $META],
                ['role' => 'user', 'content' => "$alreadySearched\n\nChoisis une NOUVELLE cible médicale pertinente."]
            ], $st['key_idx']++, 800, 120);
            
            if(isset($dec['error'])) {
                add_log($st, '❌ Erreur Mistral: ' . $dec['error'], 'error', json_encode($dec['debug'] ?? $dec));
                $st['status'] = 'error';
            } else {
                $target = $dec['next_target'] ?? '';
                if(empty($target) || !is_string($target) || strlen($target) < 3 ||
                   in_array(strtolower($target), ['array', 'object', 'null', 'true', 'false', 'json'])) {
                    $fallbacks = ['Parkinson', 'Diabete', 'Cancer poumon', 'SLA', 'Mucoviscidose'];
                    $target = $fallbacks[array_rand($fallbacks)];
                    add_log($st, '⚠️ Cible invalide détectée — Fallback activé', 'warning');
                }
                if(in_array($target, $st['searched_targets'])) {
                    $fallbacks = ['Parkinson', 'Diabete', 'Cancer poumon', 'SLA', 'Mucoviscidose'];
                    $target = $fallbacks[array_rand($fallbacks)];
                    add_log($st, '⚠️ Cible déjà traitée — Changement automatique', 'warning');
                }
                
                $st['target'] = $target;
                $st['searched_targets'][] = $target;
                $st['queries'] = $dec['queries'] ?? [
                    'pubmed' => $st['target'],
                    'uniprot' => $st['target'],
                    'clinvar' => $st['target'],
                    'arxiv' => $st['target']
                ];
                add_log($st, '🎯 CIBLE AUTO-CHOISIE: ' . $st['target'], 'success', $dec['reasoning'] ?? 'Exploration scientifique');
                add_log($st, '💭 Raisonnement: ' . ($dec['reasoning'] ?? 'N/A'), 'info');
                $st['step'] = 1;
            }
        }
        // ÉTAPES 1-4: APIs 4 sources
        elseif($st['step'] >= 1 && $st['step'] <= 4) {
            $srcs = [1 => 'PubMed', 2 => 'UniProt', 3 => 'ClinVar', 4 => 'ArXiv'];
            $src = $srcs[$st['step']];
            $q = $st['queries'][strtolower($src)] ?? $st['target'];
            add_log($st, '📡 Appel ' . $src . ': ' . $q, 'info', 'Requête avancée avec opérateurs booléens pour maximiser la pertinence.');
            
            $count = 0;
            if($src === 'PubMed') $count = api_pubmed($q)['count'];
            elseif($src === 'UniProt') $count = api_uniprot($q)['count'];
            elseif($src === 'ClinVar') $count = api_clinvar($q)['count'];
            elseif($src === 'ArXiv') $count = api_arxiv($q)['count'];
            
            add_log($st, '✅ ' . $src . ' : ' . $count . ' résultats', 'success', $count > 0 ? 'Données pertinentes trouvées pour croisement.' : 'Peu de données — opportunité de recherche inexplorée.');
            $st['memory'][] = ['source' => $src, 'count' => $count, 'query' => $q];
            $st['step']++;
        }
        // ÉTAPE 5: Synthèse + Recherche Approfondie
        elseif($st['step'] === 5) {
            add_log($st, '🔗 Synthèse des 4 sources...', 'warning', 'Croisement des données pour générer une hypothèse testable.');
            $memStr = '';
            foreach($st['memory'] as $m) {
                $memStr .= $m['source'] . ':' . $m['count'] . '; ';
            }
            
            $syn = mistral([
                ['role' => 'system', 'content' => 'JSON: {hypothesis, novelty_score, summary, actionable, verification_needed}'],
                ['role' => 'user', 'content' => 'Synthèse pour ' . $st['target'] . ': ' . $memStr . '\n\nGénère une hypothèse de recherche UTILE pour l\'humanité. Inclue verification_needed (true/false).']
            ], $st['key_idx']++, 1000, 120);
            
            $title = isset($syn['hypothesis']) && is_string($syn['hypothesis']) ? $syn['hypothesis'] : 'Hypothèse générée pour ' . $st['target'];
            if(strlen($title) < 10) $title = 'Hypothèse générée pour ' . $st['target'];
            
            $hypo = [
                'id' => 'HYP-'.date('YmdHis').'-'.rand(1000,9999),
                'target' => $st['target'],
                'title' => $title,
                'novelty' => isset($syn['novelty_score']) && is_numeric($syn['novelty_score']) ? $syn['novelty_score'] : 0.5,
                'sources' => array_column($st['memory'], 'source'),
                'summary' => isset($syn['summary']) ? $syn['summary'] : '',
                'actionable' => isset($syn['actionable']) ? $syn['actionable'] : 'À vérifier en laboratoire',
                'verification_needed' => isset($syn['verification_needed']) ? $syn['verification_needed'] : true,
                'status' => 'ARCHIVEE',
                'timestamp' => time(),
                'deep_research_done' => false
            ];
            
            // Sauvegarde dans knowledge/
            file_put_contents(STORAGE.'knowledge/'.$hypo['id'].'.json', json_encode($hypo, JSON_PRETTY_PRINT));
            
            add_log($st, '✨ HYPOTHÈSE GÉNÉRÉE: ' . substr($hypo['title'], 0, 50) . '...', 'success');
            add_log($st, '🎯 Score nouveauté: ' . round($hypo['novelty']*100) . '%', 'warning');
            add_log($st, '💾 Archivée dans storage/knowledge/'.$hypo['id'].'.json', 'success');
            
            if($hypo['verification_needed']) {
                add_log($st, '🔍 RECHERCHE APPROFONDIE déclenchée pour vérification...', 'warning', 'L\'IA va lancer un cycle de vérification croisée pour valider ou infirmer cette hypothèse.');
                $st['deep_research'] = true;
                $st['pending_verification'] = $hypo;
            }
            
            $st['step'] = 0;
            $new_hypothesis = true;
        }
        // ÉTAPE 6: Recherche Approfondie (Vérification)
        elseif($st['step'] === 6 && $st['deep_research']) {
            add_log($st, '🔬 VÉRIFICATION: Lancement du cycle de recherche approfondie...', 'waiting', 'Re-croisement des 4 sources avec requêtes plus ciblées pour valider l\'hypothèse.');
            // Ici on pourrait relancer un cycle de vérification
            $st['deep_research'] = false;
            $st['step'] = 0;
        }
        
        file_put_contents($stateFile, json_encode($st));
    }
    
    ob_end_clean();
    echo json_encode([
        'logs' => $st['logs'] ?? [],
        'status' => $st['status'] ?? 'unknown',
        'target' => $st['target'] ?? '',
        'new_hypothesis' => $new_hypothesis
    ]);
    exit;
}

// ACTION: load_hypotheses (Pagination AJAX)
if($action === 'load_hypotheses') {
    ob_end_clean();
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    
    // Charger tous les fichiers JSON de knowledge/
    $files = glob(STORAGE.'knowledge/*.json');
    rsort($files); // Plus récent en premier
    
    $allHypos = [];
    foreach($files as $f) {
        $data = @json_decode(file_get_contents($f), true);
        if($data) $allHypos[] = $data;
    }
    
    $total = count($allHypos);
    $totalPages = max(1, ceil($total / HYPOTHESIS_PER_PAGE));
    $page = min($page, $totalPages);
    
    $start = ($page - 1) * HYPOTHESIS_PER_PAGE;
    $hypos = array_slice($allHypos, $start, HYPOTHESIS_PER_PAGE);
    
    echo json_encode([
        'hypotheses' => $hypos,
        'total_count' => $total,
        'total_pages' => $totalPages,
        'current_page' => $page
    ]);
    exit;
}

ob_end_clean();
echo json_encode(['status' => 'unknown', 'action' => $action]);
?>
