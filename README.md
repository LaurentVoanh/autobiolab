# GENESIS-ULTRA README

## 🇬🇧 English

### 🚀 What is GENESIS-ULTRA?
An autonomous AI scientific researcher built in PHP. It autonomously selects medical targets, queries 4 scientific databases (PubMed, UniProt, ClinVar, ArXiv), and generates testable hypotheses using Mistral AI.

### 💡 Why Revolutionary?
*   **Fully Autonomous:** Self-looping agent; no constant human prompting needed.
*   **Multi-Source Verification:** Cross-references 4 major scientific APIs for validity.
*   **Lightweight:** Pure PHP + cURL. No Python, Docker, or heavy ML frameworks required.
*   **Transparent:** Real-time logging and local JSON storage of all discoveries.

### 🛠 Installation
1.  **Requirements:** PHP 7.4+, Web Server (Apache/Nginx), cURL enabled.
2.  **Setup:**
    *   Clone repository to web root.
    *   Edit `agent.php`: Insert Mistral API keys in `$API_KEYS` array.
    *   Ensure write permissions for the `storage/` directory.
3.  **Run:** Open `index.php` in your browser.

### 🔑 Get Free Mistral API Key
1.  Visit [console.mistral.ai](https://console.mistral.ai).
2.  Sign up for a free account.
3.  Go to **API Keys** and create a new key.
4.  Copy key into `agent.php`. (Free tier available with limits).

### 👥 Who Is It For?
*   Bioinformaticians & Researchers.
*   Pharma R&D Teams.
*   Students & Science Hobbyists.
*   Developers building AI Agents.

### ⚠️ Disclaimer
For research assistance only. Not medical advice. Verify all hypotheses in a laboratory.

---

## 🇫🇷 Français

### 🚀 Qu'est-ce que GENESIS-ULTRA ?
Un chercheur scientifique IA autonome codé en PHP. Il sélectionne des cibles médicales, interroge 4 bases de données (PubMed, UniProt, ClinVar, ArXiv) et génère des hypothèses testables via Mistral AI.

### 💡 Pourquoi Révolutionnaire ?
*   **Totalement Autonome :** Agent en boucle automatique ; sans prompting humain constant.
*   **Vérification Multi-Sources :** Recoupe 4 APIs scientifiques majeures pour la validité.
*   **Léger :** 100% PHP + cURL. Aucun Python, Docker ou framework ML lourd requis.
*   **Transparent :** Logs en temps réel et stockage local JSON des découvertes.

### 🛠 Installation
1.  **Prérequis :** PHP 7.4+, Serveur Web (Apache/Nginx), cURL activé.
2.  **Configuration :**
    *   Cloner le dépôt à la racine web.
    *   Éditer `agent.php` : Insérer les clés API Mistral dans `$API_KEYS`.
    *   Vérifier les permissions d'écriture pour `storage/`.
3.  **Lancer :** Ouvrir `index.php` dans le navigateur.

### 🔑 Obtenir Clé API Mistral Gratuite
1.  Aller sur [console.mistral.ai](https://console.mistral.ai).
2.  Créer un compte gratuit.
3.  Aller dans **API Keys** et générer une clé.
4.  Copier la clé dans `agent.php`. (Offre gratuite disponible avec limites).

### 👥 Pour Qui ?
*   Bioinformaticiens & Chercheurs.
*   Équipes R&D Pharmaceutiques.
*   Étudiants & Passionnés de Sciences.
*   Développeurs d'Agents IA.

### ⚠️ Avertissement
Aide à la recherche uniquement. Ne remplace pas un avis médical. Vérifier toutes les hypothèses en laboratoire.

---

## 🇨🇳 中文

### 🚀 什么是 GENESIS-ULTRA？
一个用 PHP 构建的自主 AI 科学研究者。它自动选择医学目标，查询 4 个科学数据库（PubMed, UniProt, ClinVar, ArXiv），并使用 Mistral AI 生成可测试的假设。

### 💡 为何具有革命性？
*   **完全自主：** 自动循环代理；无需持续人工提示。
*   **多源验证：** 交叉引用 4 个主要科学 API 以确保有效性。
*   **轻量级：** 纯 PHP + cURL。无需 Python、Docker 或重型 ML 框架。
*   **透明：** 实时日志和本地 JSON 存储所有发现。

### 🛠 安装
1.  **要求：** PHP 7.4+，Web 服务器 (Apache/Nginx)，启用 cURL。
2.  **设置：**
    *   克隆仓库到 Web 根目录。
    *   编辑 `agent.php`：在 `$API_KEYS` 数组中填入 Mistral API 密钥。
    *   确保 `storage/` 目录有写入权限。
3.  **运行：** 在浏览器中打开 `index.php`。

### 🔑 获取免费 Mistral API 密钥
1.  访问 [console.mistral.ai](https://console.mistral.ai)。
2.  注册免费账户。
3.  进入 **API Keys** 创建新密钥。
4.  将密钥复制到 `agent.php`。(提供免费层级，有限额)。

### 👥 适用人群
*   生物信息学家与研究人员。
*   制药研发团队。
*   学生与科学爱好者。
*   AI 代理开发者。

### ⚠️ 免责声明
仅用于研究辅助。非医疗建议。所有假设需在实验室验证。
