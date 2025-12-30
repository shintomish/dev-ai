-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: dev_ai
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `attachments`
--

DROP TABLE IF EXISTS `attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint unsigned NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` bigint unsigned NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attachments_message_id_index` (`message_id`),
  CONSTRAINT `attachments_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attachments`
--

LOCK TABLES `attachments` WRITE;
/*!40000 ALTER TABLE `attachments` DISABLE KEYS */;
INSERT INTO `attachments` VALUES (1,11,'attachments/1767072073_test_error.log','test_error.log','text/plain',386,'[2025-12-30 10:00:00] ERROR: SQLSTATE[HY000] [2002] Connection refused\n[2025-12-30 10:00:01] Stack trace:\n  #0 /var/www/html/vendor/laravel/framework/src/Illuminate/Database/Connectors/Connector.php(70): PDO->__construct()\n  #1 /var/www/html/vendor/laravel/framework/src/Illuminate/Database/Connectors/MySqlConnector.php(24): Illuminate\\Database\\Connectors\\Connector->createConnection()','2025-12-30 14:21:13','2025-12-30 14:21:13'),(2,19,'attachments/1767072604_conversation_3_20251228_202727.txt','conversation_3_20251228_202727.txt','text/plain',801,'Exce VBAのコード質問\n==============\n\n作成日時: 2025-12-28 20:25:59\nモード: 開発支援\n\n--------------------------------------------------------------------------------\n\n[ユーザー]\nExce VBAのコード質問\n\n--------------------------------------------------------------------------------\n\n[AI]\nExcel VBAのコードについて質問をどうぞ！\n\n以下のような内容でお答えできます：\n- エラーの原因特定と修正方法\n- 処理の最適化・高速化\n- 動作するサンプルコード\n- セル操作、ファイル処理、ループ処理など\n\n具体的なコードやエラーメッセージを貼り付けていただければ、より的確に回答できます。\n\n--------------------------------------------------------------------------------\n\n','2025-12-30 14:30:04','2025-12-30 14:30:04'),(3,21,'attachments/1767072644_chat_js_fixed.js','chat_js_fixed.js','text/plain',8656,'// ===== ファイルアップロード処理（修正版） =====\n\nasync function handleFileUpload(message, mode, conversationId, fileInput) {\n    const loadingId = appendMessage(\'assistant\', \'考え中...\', true);\n\n    try {\n        const formData = new FormData();\n        formData.append(\'message\', message);\n        formData.append(\'mode\', mode);\n        if (conversationId) {\n            formData.append(\'conversation_id\', conversationId);\n        }\n\n        // ファイルを追加\n        Array.from(fileInput.files).forEach(file => {\n            formData.append(\'files[]\', file);\n        });\n\n        console.log(\'Uploading files:\', Array.from(fileInput.files).map(f => f.name));\n\n        const response = await fetch(\'{{ route(\"chat.send\") }}\', {\n            method: \'POST\',\n            headers: {\n                \'X-CSRF-TOKEN\': csrfToken,\n                \'Accept\': \'application/json\',  // JSONレスポンスを期待\n            },\n            body: formData,\n        });\n\n        console.log(\'Response status:\', response.status);\n        console.log(\'Response content-type:\', response.headers.get(\'content-type\'));\n\n        // HTTPステータスチェック\n        if (!response.ok) {\n            const errorText = await response.text();\n            console.error(\'Server Error Response:\', errorText);\n            \n            // HTMLエラーページが返ってきた場合\n            if (errorText.includes(\'<!DOCTYPE\') || errorText.includes(\'<html\')) {\n                throw new Error(`サーバーエラー (${response.status}): ページが見つからないか、サーバー内部エラーが発生しました`);\n            }\n            \n            // JSONエラーレスポンスの場合\n            try {\n                const errorData = JSON.parse(errorText);\n                throw new Error(errorData.message || errorData.error || `HTTP ${response.status} エラー`);\n            } catch (parseError) {\n                throw new Error(`HTTP ${response.status}: ${errorText.substring(0, 100)}`);\n            }\n        }\n\n        // Content-Typeチェック\n        const contentType = response.headers.get(\'content-type\');\n        if (!contentType || !contentType.includes(\'application/json\')) {\n            const text = await response.text();\n            console.error(\'Non-JSON Response:\', text.substring(0, 500));\n            throw new Error(\'サーバーから正しいJSON応答が返されませんでした。HTMLページが返ってきています。\');\n        }\n\n        // JSONパース\n        const data = await response.json();\n        console.log(\'Response data:\', data);\n        \n        // ローディング表示を削除\n        document.getElementById(loadingId)?.remove();\n\n        if (data.success) {\n            appendMessage(\'assistant\', data.response);\n\n            // 新しい会話の場合、URLを更新\n            if (data.conversation_id && !conversationIdInput.value) {\n                conversationIdInput.value = data.conversation_id;\n                window.history.replaceState({}, \'\', `/chat?conversation=${data.conversation_id}`);\n                setTimeout(() => location.reload(), 1000);\n            }\n        } else {\n            appendMessage(\'error\', `エラー: ${data.error || \'不明なエラーが発生しました\'}`);\n        }\n        \n    } catch (error) {\n        document.getElementById(loadingId)?.remove();\n        console.error(\'Upload Error:\', error);\n        appendMessage(\'error\', `アップロードエラー: ${error.message}`);\n    }\n}\n\n// ===== 通常のテキスト送信（修正版） =====\n\nasync function handleNormalResponse(message, mode, conversationId) {\n    const loadingId = appendMessage(\'assistant\', \'考え中...\', true);\n\n    try {\n        const response = await fetch(\'{{ route(\"chat.send\") }}\', {\n            method: \'POST\',\n            headers: {\n                \'X-CSRF-TOKEN\': csrfToken,\n                \'Content-Type\': \'application/json\',\n                \'Accept\': \'application/json\',\n            },\n            body: JSON.stringify({\n                message: message,\n                mode: mode,\n                conversation_id: conversationId,\n            }),\n        });\n\n        console.log(\'Response status:\', response.status);\n\n        if (!response.ok) {\n            const errorText = await response.text();\n            console.error(\'Server Error:\', errorText);\n            \n            if (errorText.includes(\'<!DOCTYPE\')) {\n                throw new Error(`サーバーエラー (${response.status})`);\n            }\n            \n            try {\n                const errorData = JSON.parse(errorText);\n                throw new Error(errorData.message || errorData.error || `HTTP ${response.status}`);\n            } catch (e) {\n                throw new Error(`HTTP ${response.status}: サーバーエラー`);\n            }\n        }\n\n        const contentType = response.headers.get(\'content-type\');\n        if (!contentType?.includes(\'application/json\')) {\n            const text = await response.text();\n            console.error(\'Non-JSON Response:\', text.substring(0, 500));\n            throw new Error(\'サーバーから正しい応答が返されませんでした\');\n        }\n\n        const data = await response.json();\n        document.getElementById(loadingId)?.remove();\n\n        if (data.success) {\n            appendMessage(\'assistant\', data.response);\n\n            if (data.conversation_id && !conversationIdInput.value) {\n                conversationIdInput.value = data.conversation_id;\n                window.history.replaceState({}, \'\', `/chat?conversation=${data.conversation_id}`);\n                setTimeout(() => location.reload(), 1000);\n            }\n        } else {\n            appendMessage(\'error\', `エラー: ${data.error}`);\n        }\n        \n    } catch (error) {\n        document.getElementById(loadingId)?.remove();\n        console.error(\'Request Error:\', error);\n        appendMessage(\'error\', `エラー: ${error.message}`);\n    }\n}\n\n// ===== ストリーミング応答（修正版） =====\n\nasync function handleStreamingResponse(message, mode, conversationId) {\n    const messageId = appendStreamingMessage();\n\n    try {\n        const response = await fetch(\'{{ route(\"chat.send.stream\") }}\', {\n            method: \'POST\',\n            headers: {\n                \'X-CSRF-TOKEN\': csrfToken,\n                \'Content-Type\': \'application/json\',\n                \'Accept\': \'text/event-stream\',\n            },\n            body: JSON.stringify({\n                message: message,\n                mode: mode,\n                conversation_id: conversationId,\n            }),\n        });\n\n        if (!response.ok) {\n            const errorText = await response.text();\n            console.error(\'Streaming Error:\', errorText);\n            throw new Error(`HTTP ${response.status}: ストリーミング接続に失敗しました`);\n        }\n\n        const reader = response.body.getReader();\n        const decoder = new TextDecoder();\n        let buffer = \'\';\n\n        while (true) {\n            const { done, value } = await reader.read();\n            if (done) break;\n\n            buffer += decoder.decode(value, { stream: true });\n            const lines = buffer.split(\'\\n\');\n            buffer = lines.pop();\n\n            for (const line of lines) {\n                if (line.startsWith(\'data: \')) {\n                    try {\n                        const data = JSON.parse(line.slice(6));\n\n                        if (data.text) {\n                            appendTextToStreamingMessage(messageId, data.text);\n                        }\n\n                        if (data.done) {\n                            if (data.conversation_id && !conversationIdInput.value) {\n                                conversationIdInput.value = data.conversation_id;\n                                window.history.replaceState({}, \'\', `/chat?conversation=${data.conversation_id}`);\n                                setTimeout(() => location.reload(), 1000);\n                            }\n                        }\n\n                        if (data.error) {\n                            throw new Error(data.error);\n                        }\n                    } catch (parseError) {\n                        console.error(\'JSON Parse Error:\', parseError, \'Line:\', line);\n                    }\n                }\n            }\n        }\n\n        finalizeStreamingMessage(messageId);\n\n    } catch (error) {\n        console.error(\'Streaming Error:\', error);\n        document.getElementById(messageId)?.remove();\n        appendMessage(\'error\', `ストリーミングエラー: ${error.message}`);\n    }\n}\n','2025-12-30 14:30:44','2025-12-30 14:30:44'),(4,23,'attachments/1767073451_longtext.log','longtext.log','text/x-php',105,'<?php\r\n   $veryLongVariableName = \"ThisIsAVeryLongStringWithoutSpacesThisIsAVeryLongStringWithoutSpaces\";','2025-12-30 14:44:11','2025-12-30 14:44:11'),(5,25,'attachments/1767073538_test1230_error.log','test1230_error.log','text/plain',386,'[2025-12-30 10:00:00] ERROR: SQLSTATE[HY000] [2002] Connection refused\n[2025-12-30 10:00:01] Stack trace:\n  #0 /var/www/html/vendor/laravel/framework/src/Illuminate/Database/Connectors/Connector.php(70): PDO->__construct()\n  #1 /var/www/html/vendor/laravel/framework/src/Illuminate/Database/Connectors/MySqlConnector.php(24): Illuminate\\Database\\Connectors\\Connector->createConnection()','2025-12-30 14:45:38','2025-12-30 14:45:38');
/*!40000 ALTER TABLE `attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversation_tag`
--

DROP TABLE IF EXISTS `conversation_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversation_tag` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `tag_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversation_tag_conversation_id_tag_id_unique` (`conversation_id`,`tag_id`),
  KEY `conversation_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `conversation_tag_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversation_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversation_tag`
--

LOCK TABLES `conversation_tag` WRITE;
/*!40000 ALTER TABLE `conversation_tag` DISABLE KEYS */;
INSERT INTO `conversation_tag` VALUES (1,1,1,NULL,NULL),(2,1,2,NULL,NULL),(5,3,4,NULL,NULL);
/*!40000 ALTER TABLE `conversation_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mode` enum('dev','study') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dev',
  `is_favorite` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversations_is_favorite_index` (`is_favorite`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversations`
--

LOCK TABLES `conversations` WRITE;
/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
INSERT INTO `conversations` VALUES (1,'laravelのrootingを教えて','dev',0,'2025-12-29 16:22:15','2025-12-29 16:22:25'),(2,'Dockerの基本コマンド','dev',0,'2025-12-30 06:48:59','2025-12-30 15:52:50'),(3,'エラーログを解析してください。','dev',0,'2025-12-30 14:21:13','2025-12-30 14:30:54'),(4,'添付ファイルを解析して','dev',1,'2025-12-30 14:44:11','2025-12-30 15:52:29'),(6,'Laravelとは何ですか？','dev',0,'2025-12-30 15:27:02','2025-12-30 15:28:05'),(8,'phpとは何ですか？','dev',0,'2025-12-30 15:46:35','2025-12-30 15:46:35'),(9,'小学生1年生のScratch','study',0,'2025-12-30 18:04:17','2025-12-30 18:04:31');
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `role` enum('user','assistant') COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_conversation_id_index` (`conversation_id`),
  CONSTRAINT `messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_chk_1` CHECK (json_valid(`metadata`))
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (1,1,'user','laravelのrootingを教えて',NULL,'2025-12-29 16:22:15','2025-12-29 16:22:15'),(2,1,'assistant','# Laravelルーティング基本\n\n**結論：** `routes/web.php`でURLとコントローラーを紐付け、RESTfulなルート定義が可能。基本パターンを覚えれば8割は解決します。\n\n## 基本的なルート定義\n\n```php\n// routes/web.php\nuse App\\Http\\Controllers\\UserController;\n\n// 基本形\nRoute::get(\'/users\', [UserController::class, \'index\']);\nRoute::post(\'/users\', [UserController::class, \'store\']);\nRoute::get(\'/users/{id}\', [UserController::class, \'show\']);\nRoute::put(\'/users/{id}\', [UserController::class, \'update\']);\nRoute::delete(\'/users/{id}\', [UserController::class, \'destroy\']);\n```\n\n## RESTfulリソースルート（推奨）\n\n```php\n// 上記5つのルートを一括定義\nRoute::resource(\'users\', UserController::class);\n\n// 一部だけ使用\nRoute::resource(\'users\', UserController::class)->only([\'index\', \'show\']);\nRoute::resource(\'users\', UserController::class)->except([\'destroy\']);\n```\n\n## パラメータ付きルート\n\n```php\n// 必須パラメータ\nRoute::get(\'/users/{id}\', [UserController::class, \'show\']);\n\n// オプションパラメータ\nRoute::get(\'/users/{id?}\', [UserController::class, \'show\']);\n\n// 正規表現制約\nRoute::get(\'/users/{id}\', [UserController::class, \'show\'])->where(\'id\', \'[0-9]+\');\n```\n\n## ルートグループ\n\n```php\n// プレフィックス + ミドルウェア\nRoute::prefix(\'admin\')->middleware(\'auth\')->group(function () {\n    Route::get(\'/users\', [AdminController::class, \'users\']);\n    Route::get(\'/posts\', [AdminController::class, \'posts\']);\n});\n```\n\n## 名前付きルート\n\n```php\nRoute::get(\'/users/{id}\', [UserController::class, \'show\'])->name(\'users.show\');\n\n// Bladeで使用\n<a href=\"{{ route(\'users.show\', $user->id) }}\">詳細</a>\n```\n\n現在のルート確認：`php artisan route:list`','{\"usage\":{\"input_tokens\":409,\"cache_creation_input_tokens\":0,\"cache_read_input_tokens\":0,\"cache_creation\":{\"ephemeral_5m_input_tokens\":0,\"ephemeral_1h_input_tokens\":0},\"output_tokens\":587,\"service_tier\":\"standard\"},\"model\":\"claude-sonnet-4-20250514\"}','2025-12-29 16:22:25','2025-12-29 16:22:25'),(3,2,'user','Dockerの基本コマンド',NULL,'2025-12-30 06:48:59','2025-12-30 06:48:59'),(4,2,'assistant','**Docker基本コマンド一覧**\n\n## コンテナ操作\n```bash\n# イメージからコンテナ起動\ndocker run -d --name myapp nginx\n\n# 既存コンテナ開始/停止\ndocker start myapp\ndocker stop myapp\n\n# コンテナ一覧表示\ndocker ps          # 実行中のみ\ndocker ps -a       # 全て\n\n# コンテナ削除\ndocker rm myapp\ndocker rm -f myapp  # 強制削除\n```\n\n## イメージ操作\n```bash\n# イメージ一覧\ndocker images\n\n# イメージ取得\ndocker pull nginx:latest\n\n# イメージ削除\ndocker rmi nginx:latest\n\n# イメージビルド\ndocker build -t myapp:1.0 .\n```\n\n## ログ・デバッグ\n```bash\n# ログ確認\ndocker logs myapp\ndocker logs -f myapp    # リアルタイム\n\n# コンテナ内でコマンド実行\ndocker exec -it myapp bash\ndocker exec myapp ls /var/log\n```\n\n## 便利コマンド\n```bash\n# 全停止コンテナ削除\ndocker container prune\n\n# 未使用イメージ削除\ndocker image prune\n\n# システム情報\ndocker info\ndocker version\n```\n\nLaravel開発なら `docker-compose` も併用することが多いですが、まずはこれらの基本コマンドから始めることをお勧めします。','{\"usage\":{\"input_tokens\":408,\"cache_creation_input_tokens\":0,\"cache_read_input_tokens\":0,\"cache_creation\":{\"ephemeral_5m_input_tokens\":0,\"ephemeral_1h_input_tokens\":0},\"output_tokens\":396,\"service_tier\":\"standard\"},\"model\":\"claude-sonnet-4-20250514\"}','2025-12-30 06:48:59','2025-12-30 06:48:59'),(5,1,'user','このエラーログの原因と解決方法を教えてください',NULL,'2025-12-30 08:23:17','2025-12-30 08:23:17'),(6,1,'user','ログファイルを解析して',NULL,'2025-12-30 10:25:02','2025-12-30 10:25:02'),(7,1,'user','このエラーログの原因と解決方法を教えてください',NULL,'2025-12-30 10:37:18','2025-12-30 10:37:18'),(8,1,'user','このエラーログの原因と解決方法を教えてください',NULL,'2025-12-30 10:39:57','2025-12-30 10:39:57'),(9,1,'user','このエラーログの原因と解決方法を教えてください',NULL,'2025-12-30 11:30:43','2025-12-30 11:30:43'),(10,1,'user','ログファイルを解析して',NULL,'2025-12-30 11:41:27','2025-12-30 11:41:27'),(11,3,'user','エラーログを解析してください。',NULL,'2025-12-30 14:21:13','2025-12-30 14:21:13'),(12,3,'assistant','**結論：MySQLサーバーが起動していないか、接続設定が間違っています。**\n\n## 原因分析\n`SQLSTATE[HY000] [2002] Connection refused` は MySQL/MariaDB への接続が拒否されている状態です。\n\n## 解決手順\n\n### 1. MySQLサービス状況確認\n```bash\n# サービス状態確認\nsudo systemctl status mysqld\n# または MariaDB の場合\nsudo systemctl status mariadb\n```\n\n### 2. サービス起動（停止している場合）\n```bash\n# MySQL\nsudo systemctl start mysqld\nsudo systemctl enable mysqld\n\n# MariaDB\nsudo systemctl start mariadb\nsudo systemctl enable mariadb\n```\n\n### 3. Laravel設定確認\n`.env` ファイルの DB 設定をチェック：\n```bash\ncat .env | grep DB_\n```\n\n正しい設定例：\n```\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=your_database_name\nDB_USERNAME=your_username\nDB_PASSWORD=your_password\n```\n\n### 4. 接続テスト\n```bash\n# 直接MySQL接続テスト\nmysql -h 127.0.0.1 -u your_username -p your_database_name\n\n# Laravel設定キャッシュクリア\nphp artisan config:clear\nphp artisan config:cache\n```\n\n### 5. ポート確認\n```bash\n# MySQLポートが開いているか確認\nsudo netstat -tlnp | grep 3306\n```\n\n最も可能性が高いのは **MySQLサービスが停止している** ことです。手順1,2を優先して実行してください。','{\"usage\":{\"input_tokens\":595,\"cache_creation_input_tokens\":0,\"cache_read_input_tokens\":0,\"cache_creation\":{\"ephemeral_5m_input_tokens\":0,\"ephemeral_1h_input_tokens\":0},\"output_tokens\":457,\"service_tier\":\"standard\"},\"model\":\"claude-sonnet-4-20250514\"}','2025-12-30 14:21:22','2025-12-30 14:21:22'),(13,3,'user','sudo netstat -tlnp | grep 3306　？',NULL,'2025-12-30 14:22:36','2025-12-30 14:22:36'),(14,3,'assistant','',NULL,'2025-12-30 14:22:47','2025-12-30 14:22:47'),(15,3,'user','PDO->__construct()',NULL,'2025-12-30 14:24:20','2025-12-30 14:24:20'),(16,3,'assistant','',NULL,'2025-12-30 14:24:29','2025-12-30 14:24:29'),(17,3,'user','PDO->__construct()',NULL,'2025-12-30 14:24:37','2025-12-30 14:24:37'),(18,3,'assistant','**PDOの基本的なコンストラクタ構文です。データベース接続を作成します。**\n\n## 基本構文\n```php\n$pdo = new PDO($dsn, $username, $password, $options);\n```\n\n## 実用的な接続例\n\n### MySQL接続\n```php\ntry {\n    $pdo = new PDO(\n        \'mysql:host=localhost;dbname=your_database;charset=utf8mb4\',\n        \'username\',\n        \'password\',\n        [\n            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n            PDO::ATTR_EMULATE_PREPARES => false\n        ]\n    );\n} catch (PDOException $e) {\n    die(\'接続失敗: \' . $e->getMessage());\n}\n```\n\n### 推奨オプション設定\n```php\n$options = [\n    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,           // 例外モード\n    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      // 連想配列で取得\n    PDO::ATTR_EMULATE_PREPARES => false,                   // 本当のプリペア使用\n    PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4\"    // 文字セット設定\n];\n```\n\n## Laravel環境での確認方法\n```php\n// Laravelでは通常DB::connection()->getPdo()で取得\n$pdo = DB::connection()->getPdo();\necho get_class($pdo); // PDO\n```\n\n**セキュリティ注意**: 本番環境では接続情報を`.env`ファイルに分離し、エラー表示は無効化してください。','{\"usage\":{\"input_tokens\":405,\"cache_creation_input_tokens\":0,\"cache_read_input_tokens\":0,\"cache_creation\":{\"ephemeral_5m_input_tokens\":0,\"ephemeral_1h_input_tokens\":0},\"output_tokens\":492,\"service_tier\":\"standard\"},\"model\":\"claude-sonnet-4-20250514\"}','2025-12-30 14:24:47','2025-12-30 14:24:47'),(19,3,'user','ログファイル解析して',NULL,'2025-12-30 14:30:04','2025-12-30 14:30:04'),(20,3,'assistant','**結論：これはログファイルではなく、会話履歴ファイルです。**\n技術的な問題やエラーは含まれていません。\n\n## ファイル内容の分析\n\n```\nファイル形式: 会話履歴（.txt）\n作成日時: 2025-12-28 20:25:59\n内容: Excel VBA質問の開始部分\n```\n\n### 含まれる情報\n- ユーザーがExcel VBAについて質問したい旨を表明\n- AI側が対応可能な範囲を提示（エラー解析、最適化、サンプルコードなど）\n- 実際の技術的な質問やコードは未投稿の状態\n\n### 技術サポートが必要な場合\nExcel VBAで困っていることがあれば、以下を教えてください：\n\n1. **具体的なコード**（エラーが出ている箇所）\n2. **エラーメッセージ**（正確な文言）\n3. **やりたいこと**（要件・目的）\n4. **現在の状況**（どこまでできているか）\n\n実際のコードやエラーがあれば、すぐに解決策を提示できます。','{\"usage\":{\"input_tokens\":655,\"cache_creation_input_tokens\":0,\"cache_read_input_tokens\":0,\"cache_creation\":{\"ephemeral_5m_input_tokens\":0,\"ephemeral_1h_input_tokens\":0},\"output_tokens\":348,\"service_tier\":\"standard\"},\"model\":\"claude-sonnet-4-20250514\"}','2025-12-30 14:30:12','2025-12-30 14:30:12'),(21,3,'user','エラー解析して',NULL,'2025-12-30 14:30:44','2025-12-30 14:30:44'),(22,3,'assistant','**エラーの原因：Bladeテンプレート構文がJavaScriptファイルで使用されている**\n\n`.js`ファイル内で`{{ route(\"chat.send\") }}`が使われていますが、これはBladeテンプレートでしか動作しません。\n\n## 解決策：設定値をHTMLから渡す\n\n### 1. HTML側（Bladeテンプレート）でルートを定義\n```html\n<script>\nwindow.chatConfig = {\n    sendUrl: \'{{ route(\"chat.send\") }}\',\n    streamUrl: \'{{ route(\"chat.send.stream\") }}\',\n    csrfToken: \'{{ csrf_token() }}\'\n};\n</script>\n<script src=\"{{ asset(\'js/chat.js\') }}\"></script>\n```\n\n### 2. JavaScript側で設定値を使用\n```javascript\n// 設定値を取得\nconst { sendUrl, streamUrl, csrfToken } = window.chatConfig;\n\n// fetch URLを変更\nconst response = await fetch(sendUrl, {\n    method: \'POST\',\n    headers: {\n        \'X-CSRF-TOKEN\': csrfToken,\n        \'Accept\': \'application/json\',\n    },\n    body: formData,\n});\n```\n\n### 3. 全体修正例（重要部分のみ）\n```javascript\n// ファイルの先頭で設定を取得\nconst config = window.chatConfig || {};\n\nasync function handleFileUpload(message, mode, conversationId, fileInput) {\n    // ...\n    const response = await fetch(config.sendUrl, {\n        method: \'POST\',\n        headers: {\n            \'X-CSRF-TOKEN\': config.csrfToken,\n            \'Accept\': \'application/json\',\n        },\n        body: formData,\n    });\n    // ...\n}\n```\n\n**推奨：**\n- JS を`.blade.php`から`.js`ファイルに分離\n- 設定値は HTML の`<script>`タグで渡す\n- CSRFトークンも同様に渡す\n\nこれで JavaScript ファイルが正常に動作します。','{\"usage\":{\"input_tokens\":2618,\"cache_creation_input_tokens\":0,\"cache_read_input_tokens\":0,\"cache_creation\":{\"ephemeral_5m_input_tokens\":0,\"ephemeral_1h_input_tokens\":0},\"output_tokens\":548,\"service_tier\":\"standard\"},\"model\":\"claude-sonnet-4-20250514\"}','2025-12-30 14:30:54','2025-12-30 14:30:54'),(23,4,'user','添付ファイルを解析して',NULL,'2025-12-30 14:44:11','2025-12-30 14:44:11'),(24,4,'assistant','**結論：** PHPコード自体に構文エラーはありませんが、可読性とメンテナンス性に問題があります。長すぎる変数名と文字列の改善が必要です。\n\n## コード分析結果\n\n### 現在のコード\n```php\n<?php\n$veryLongVariableName = \"ThisIsAVeryLongStringWithoutSpacesThisIsAVeryLongStringWithoutSpaces\";\n```\n\n### 問題点\n1. **変数名が長すぎる** - PSR-12準拠ではcamelCaseで適切な長さに\n2. **文字列が長すぎる** - 可読性が悪く、メンテナンスしにくい\n3. **用途が不明** - 変数の目的がコードから読み取れない\n\n### 改善例\n\n**Option 1: 基本的な改善**\n```php\n<?php\n$longText = \"ThisIsAVeryLongStringWithoutSpaces\" . \n            \"ThisIsAVeryLongStringWithoutSpaces\";\n```\n\n**Option 2: 用途を明確化**\n```php\n<?php\n// 設定値やテンプレートの場合\n$templateString = \"ThisIsAVeryLongStringWithoutSpaces\" .\n                  \"ThisIsAVeryLongStringWithoutSpaces\";\n\n// テストデータの場合\n$testData = str_repeat(\"ThisIsAVeryLongStringWithoutSpaces\", 2);\n```\n\n**Option 3: 定数として定義**\n```php\n<?php\ndefine(\'LONG_TEXT_CONSTANT\', \n       \'ThisIsAVeryLongStringWithoutSpaces\' .\n       \'ThisIsAVeryLongStringWithoutSpaces\');\n```\n\nこのコードの用途を教えていただけますか？より具体的な改善案を提示できます。','{\"usage\":{\"input_tokens\":491,\"cache_creation_input_tokens\":0,\"cache_read_input_tokens\":0,\"cache_creation\":{\"ephemeral_5m_input_tokens\":0,\"ephemeral_1h_input_tokens\":0},\"output_tokens\":496,\"service_tier\":\"standard\"},\"model\":\"claude-sonnet-4-20250514\"}','2025-12-30 14:44:23','2025-12-30 14:44:23'),(25,4,'user','エラーログを解析して',NULL,'2025-12-30 14:45:38','2025-12-30 14:45:38'),(26,4,'assistant','**結論**: MySQLサーバーが起動していないか、接続設定が間違っています。まずMySQLの状態確認 → 起動 → Laravel設定確認の順で対処してください。\n\n## 1. MySQLサーバー状態確認・起動\n\n```bash\n# MySQL状態確認\nsudo systemctl status mysqld\n# または\nsudo systemctl status mysql\n\n# 停止している場合は起動\nsudo systemctl start mysqld\nsudo systemctl enable mysqld  # 自動起動設定\n```\n\n## 2. Laravel設定確認\n\n`.env`ファイルの設定を確認：\n\n```env\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=your_database_name\nDB_USERNAME=your_username\nDB_PASSWORD=your_password\n```\n\n## 3. 接続テスト\n\n```bash\n# MySQLに直接接続テスト\nmysql -h 127.0.0.1 -P 3306 -u your_username -p\n\n# Laravel設定キャッシュクリア\nphp artisan config:clear\nphp artisan config:cache\n```\n\n## 4. よくある原因\n\n- MySQLサービスが未起動（最も多い）\n- ポート3306がブロックされている\n- データベースユーザーの権限不足\n- `.env`のDB_HOSTがlocalhostではなく127.0.0.1を使用\n\nエラーが続く場合は`sudo netstat -tlnp | grep 3306`でポート状態も確認してください。','{\"usage\":{\"input_tokens\":595,\"cache_creation_input_tokens\":0,\"cache_read_input_tokens\":0,\"cache_creation\":{\"ephemeral_5m_input_tokens\":0,\"ephemeral_1h_input_tokens\":0},\"output_tokens\":418,\"service_tier\":\"standard\"},\"model\":\"claude-sonnet-4-20250514\"}','2025-12-30 14:45:46','2025-12-30 14:45:46'),(31,6,'user','Laravelとは何ですか？',NULL,'2025-12-30 15:27:02','2025-12-30 15:27:02'),(32,6,'assistant','',NULL,'2025-12-30 15:27:13','2025-12-30 15:27:13'),(33,6,'user','Laravelとは何ですか？',NULL,'2025-12-30 15:27:57','2025-12-30 15:27:57'),(34,6,'assistant','**Laravel は PHP の代表的な Web アプリケーションフレームワークです。**\nMVC アーキテクチャを採用し、高速開発と保守性の両立を実現しています。\n特に API 開発、管理画面、EC サイトなどの業務システムで広く使われています。\n\n## 主要機能\n\n**ルーティング**\n```php\nRoute::get(\'/users\', [UserController::class, \'index\']);\nRoute::post(\'/users\', [UserController::class, \'store\']);\n```\n\n**Eloquent ORM（データベース操作）**\n```php\n// モデル定義\nclass User extends Model {\n    protected $fillable = [\'name\', \'email\'];\n}\n\n// データ取得\n$users = User::where(\'status\', \'active\')->get();\n```\n\n**Blade テンプレート**\n```php\n{{-- resources/views/users/index.blade.php --}}\n@foreach($users as $user)\n    <p>{{ $user->name }}</p>\n@endforeach\n```\n\n**バリデーション**\n```php\n$request->validate([\n    \'name\' => \'required|string|max:255\',\n    \'email\' => \'required|email|unique:users\'\n]);\n```\n\n## Laravel が選ばれる理由\n\n- **Artisan コマンド** - マイグレーション、モデル生成が簡単\n- **豊富なパッケージ** - 認証、キュー、メール送信等が標準搭載\n- **強力なエコシステム** - Laravel Forge、Vapor等のデプロイツール\n\n業務システム開発なら Laravel 一択といえるほど成熟したフレームワークです。','{\"usage\":{\"input_tokens\":409,\"cache_creation_input_tokens\":0,\"cache_read_input_tokens\":0,\"cache_creation\":{\"ephemeral_5m_input_tokens\":0,\"ephemeral_1h_input_tokens\":0},\"output_tokens\":454,\"service_tier\":\"standard\"},\"model\":\"claude-sonnet-4-20250514\"}','2025-12-30 15:28:05','2025-12-30 15:28:05'),(37,8,'user','phpとは何ですか？',NULL,'2025-12-30 15:46:35','2025-12-30 15:46:35'),(38,8,'assistant','PHPについて説明いたします。\n\n## PHPとは\n\n**PHP**（PHP: Hypertext Preprocessor）は、主にWebア開発で使用されるサーバーサイドスクリプト言語です。\n\n## 主な特徴\n\n### 1. **サーバーサイド言語**\n- サーバー上で実行される\n- HTMLにで動的なWebページを生成\n\n### 2. **オープンソース**\n- 無料で使用可能\n- 活る開発\n\n### 3. **クロスプラットフォーム**\n- Windows、Linux、macSで動作\n- 多様なWebサーバー（Apache、Nginx等）に対応\n\n##文例\n\n```php\n<?php\n// 基本的な出力\necho \"Hello, World!\";\n\n// 変数の使郎\";\necho \"こんにちは、\" . $name . \"さん\";\n\n// HTMLとの組\n<html>\n<body>\n    <h1><?php echo $title; ?></h1>\n    <p>現在の時刻:-d H:i:s\'); ?></p>\n</body>\n</html>\n```\n\n## 主な用途\n\n-Webサイト開発**：WordPress、ECサイト等\n- **WebAPI、GraphQL等\n- **CMS開発**：コンテンツ管理システム\n- **データベース連、PostgreSQL等\n\n## 人気フレームワーク\n\n- **Laravel**：モダンで能\n- **Symfony**：コンポーネント指向\n- **CodeIgniter**：軽量でシンプル\n- **CakePHP**：高速開発向け\n\nPH習コストが低く、豊富なドキュメントがあるため、Web開発の入門言語としても適しています。',NULL,'2025-12-30 15:46:45','2025-12-30 15:46:45'),(39,9,'user','小学生1年生のScratch',NULL,'2025-12-30 18:04:18','2025-12-30 18:04:18'),(40,9,'assistant','こんにちは！小学1年生のお子さんがScratchに興味を持ってくれたんですね。それは素晴らしいことです！✨\n\nScratchは、まさに小学1年生にぴったりのプログラミング学習ツールです。文字が読めなくても、色とりどりのブロックを組み合わせて、まるでレゴブロックで遊ぶように楽しくプログラミングができますよ！\n\n## 小学1年生におすすめのScratchの始め方\n\n### 🎯 **まずは触って遊ぼう！**\n- **ネコのキャラクター（スプライト）を動かしてみる**\n  - 「10歩動かす」ブロックをクリックしてみよう\n  - ネコがちょこちょこ動く様子が楽しい！\n\n### 🎨 **簡単にできる楽しい活動**\n1. **ネコをお散歩させよう**\n   - 右向き矢印キーを押したら右に動く\n   - 左向き矢印キーを押したら左に動く\n\n2. **色を変えて遊ぼう**\n   - ネコの色をカラフルに変える\n   - 虹色にキラキラ光らせる\n\n3. **音を鳴らしてみよう**\n   - クリックしたら「ニャー」と鳴く\n   - 楽器の音を鳴らす\n\n### 👨‍👩‍👧‍👦 **保護者の方へのアドバイス**\n- **一緒に楽しむことが一番大切**\n- 「すごいね！」「面白いね！」と褒めてあげてください\n- 間違いや失敗を恐れず、自由に試させてあげましょう\n- 30分程度の短い時間から始めるのがおすすめ\n\n### 🌟 **こんなことができるようになります**\n- 論理的な思考力（順序立てて考える力）\n- 創造力と想像力\n- 問題解決能力\n- 達成感と自信\n\n何か具体的に知りたいことがあれば、遠慮なく質問してくださいね！一緒にScratchの楽しい世界を探検しましょう！🚀','{\"usage\":{\"input_tokens\":350,\"cache_creation_input_tokens\":0,\"cache_read_input_tokens\":0,\"cache_creation\":{\"ephemeral_5m_input_tokens\":0,\"ephemeral_1h_input_tokens\":0},\"output_tokens\":679,\"service_tier\":\"standard\"},\"model\":\"claude-sonnet-4-20250514\"}','2025-12-30 18:04:31','2025-12-30 18:04:31');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_12_28_012200_create_conversations_table',1),(5,'2025_12_28_012300_create_messages_table',1),(6,'2025_12_29_140440_add_is_favorite_to_conversations_table',1),(7,'2025_12_29_160150_create_tags_table',1),(8,'2025_12_29_160200_create_conversation_tag_table',1),(10,'2025_12_30_075747_create_attachments_table',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('DDxoYxkPbYine5xBV7jUlwkYtjFH2HIfpGkuQKGa',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiaXhCNFFtdlBpQW04dlQ5OEo2UmxKUG9TbnlVUXF1Z0p2Rjh5NkNUMiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9jaGF0P2NvbnZlcnNhdGlvbj0zIjtzOjU6InJvdXRlIjtzOjEwOiJjaGF0LmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1767072357),('O5AE9OLg9wZRd4umxYL2LbHhS4d3uOKeeW7Rnsne',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiREFUQlZua3dRSUx6ZFJpaVY1V3dzeFBUUFlXMWt3ZlQzNThudkpJeSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9jaGF0P2NvbnZlcnNhdGlvbj0yIjtzOjU6InJvdXRlIjtzOjEwOiJjaGF0LmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1767077570),('r2Y3WwMwWRHg6oME8dOT6fG1of8XZJCI4tP0m77C',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRG9IWHhJQVhqZkN6VURXTmVQTUpKSm5TUmprTFBjWUZRa0NhWXpwbSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9jaGF0P2NvbnZlcnNhdGlvbj00IjtzOjU6InJvdXRlIjtzOjEwOiJjaGF0LmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1767073546);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gray',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES (1,'Laravel','red','2025-12-29 17:00:35','2025-12-29 17:00:35'),(2,'PHP','blue','2025-12-29 17:00:35','2025-12-29 17:00:35'),(3,'Git','orange','2025-12-29 17:00:35','2025-12-29 17:00:35'),(4,'Linux','yellow','2025-12-29 17:00:35','2025-12-29 17:00:35'),(5,'VBA','green','2025-12-29 17:00:35','2025-12-29 17:00:35'),(6,'JavaScript','yellow','2025-12-29 17:00:35','2025-12-29 17:00:35'),(7,'SQL','purple','2025-12-29 17:00:35','2025-12-29 17:00:35'),(8,'Docker','blue','2025-12-29 17:00:35','2025-12-29 17:00:35');
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-30 11:20:32
