# Dev AI Chat API - Postman Collection

このディレクトリには、Dev AI Chat APIをテストするためのPostmanコレクションが含まれています。

## 📦 ファイル

- `Dev-AI-API.postman_collection.json` - APIエンドポイントのコレクション
- `Dev-AI-API.postman_environment.json` - 環境変数（ローカル環境用）

## 🚀 使い方

### 1. Postmanのインストール

#### デスクトップアプリ（推奨）
https://www.postman.com/downloads/ からダウンロード

#### ブラウザ版
https://www.postman.com/

### 2. コレクションのインポート

1. Postmanを開く
2. 左上の「Import」ボタンをクリック
3. `Dev-AI-API.postman_collection.json` をドラッグ&ドロップ
4. 同様に `Dev-AI-API.postman_environment.json` もインポート

### 3. 環境の選択

右上のドロップダウンから「Dev AI - Local」環境を選択

### 4. APIテストの実行

#### ステップ1: ユーザー登録またはログイン

1. **Authentication** フォルダを開く
2. **Register** または **Login** を実行
3. トークンが自動的に環境変数に保存されます

#### ステップ2: 会話を作成

1. **Conversations** フォルダを開く
2. **Create Conversation** を実行
3. 会話IDが自動的に環境変数に保存されます

#### ステップ3: メッセージを送信

1. **Messages** フォルダを開く
2. **Send Message** を実行
3. Claude AIからの応答が返ってきます

## 📋 エンドポイント一覧

### Authentication（4個）
- `POST /api/register` - ユーザー登録
- `POST /api/login` - ログイン
- `GET /api/user` - ユーザー情報取得
- `POST /api/logout` - ログアウト

### Conversations（6個）
- `GET /api/conversations` - 会話一覧
- `POST /api/conversations` - 会話作成
- `GET /api/conversations/{id}` - 会話詳細
- `DELETE /api/conversations/{id}` - 会話削除
- `POST /api/conversations/{id}/favorite` - お気に入り切り替え
- `PUT /api/conversations/{id}/tags` - タグ更新

### Messages（2個）
- `GET /api/conversations/{id}/messages` - メッセージ一覧
- `POST /api/conversations/{id}/messages` - メッセージ送信

### Statistics（2個）
- `GET /api/stats/monthly` - 月間統計
- `GET /api/stats/detailed` - 詳細統計

## 🔧 環境変数

以下の環境変数が自動的に管理されます：

- `base_url` - APIのベースURL（デフォルト: http://localhost:8000）
- `access_token` - 認証トークン（ログイン時に自動設定）
- `user_id` - ユーザーID（ログイン時に自動設定）
- `conversation_id` - 会話ID（会話作成時に自動設定）

## 💡 ヒント

### トークンの自動管理

**Register** と **Login** のリクエストには、レスポンスからトークンを自動的に抽出して環境変数に保存するスクリプトが含まれています。

### 会話IDの自動管理

**Create Conversation** のリクエストには、作成された会話のIDを自動的に環境変数に保存するスクリプトが含まれています。

### 本番環境用の設定

本番環境用の環境ファイルを作成する場合：

1. `Dev-AI-API.postman_environment.json` をコピー
2. `base_url` を本番URLに変更（例: https://api.your-domain.com）
3. 環境名を変更（例: "Dev AI - Production"）

## 🔒 セキュリティ

- `access_token` は秘密情報として扱われます
- 環境変数は他のユーザーと共有しないでください
- 本番環境のトークンは絶対に公開しないでください

## 📝 使用例

### フルワークフロー

1. **Register** でユーザー登録 → トークン自動保存
2. **Create Conversation** で会話作成 → 会話ID自動保存
3. **Send Message** でメッセージ送信
4. **List Messages** でメッセージ履歴確認
5. **Monthly Stats** で使用統計確認

### 既存ユーザーの場合

1. **Login** でログイン → トークン自動保存
2. **List Conversations** で会話一覧確認
3. **Get Conversation** で特定の会話を表示
4. **Send Message** でメッセージ送信

## 🐛 トラブルシューティング

### 401 Unauthorized

- トークンが無効または期限切れです
- **Login** を再実行してトークンを更新してください

### 403 Forbidden

- 他のユーザーの会話にアクセスしようとしています
- 自分の会話IDを使用してください

### 環境変数が設定されない

- 環境が正しく選択されているか確認してください（右上のドロップダウン）
- リクエスト実行後、環境変数タブで値が設定されているか確認してください

## 📚 参考リンク

- [Postman Documentation](https://learning.postman.com/docs/getting-started/introduction/)
- [Dev AI Chat GitHub Repository](https://github.com/shintomish/dev-ai)

