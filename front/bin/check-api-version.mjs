#!/usr/bin/env node
import chalk from "chalk";
import dotenv from "dotenv";
import fs from "fs";
import path from "path";

dotenv.config();
if (!process.env.OPENAPI_URL) {
  console.log(chalk.yellow("⚠️  Aucune URL OPENAPI_URL trouvée."));
  process.exit(0);
}

if (!process.env.OPENAPI_BACKUP_FILE) {
  console.log(chalk.yellow("⚠️  Aucun fichier OPENAPI_BACKUP_FILE trouvé."));
  process.exit(0);
}

// Check if OPENAPI_URL is a URL or a file path
const isUrl =
  process.env.OPENAPI_URL.startsWith("http://") ||
  process.env.OPENAPI_URL.startsWith("https://");
const apiJsonPath = isUrl
  ? process.env.OPENAPI_URL
  : path.resolve(process.env.OPENAPI_URL);
const versionFilePath = path.resolve(process.env.OPENAPI_BACKUP_FILE);

if (!fs.existsSync(versionFilePath)) {
  console.log(
    chalk.yellow("⚠️  Aucun fichier de version OPENAPI_BACKUP_FILE trouvé."),
  );
  process.exit(0);
}

// Read API data from URL or file
let apiData;
if (isUrl) {
  // Fetch from URL
  try {
    const response = await fetch(apiJsonPath);
    if (!response.ok) {
      console.log(
        chalk.yellow(
          `⚠️  Erreur lors de la récupération de l'API depuis l'URL : ${response.statusText}`,
        ),
      );
      process.exit(0);
    }
    apiData = await response.json();
  } catch (error) {
    console.log(
      chalk.yellow(
        `⚠️  Erreur lors de la récupération de l'API : ${error.message}`,
      ),
    );
    process.exit(0);
  }
} else {
  // Read from file path
  if (!fs.existsSync(apiJsonPath)) {
    console.log(chalk.yellow("⚠️  Aucun fichier OPENAPI_URL trouvé."));
    process.exit(0);
  }
  apiData = JSON.parse(fs.readFileSync(apiJsonPath, "utf8"));
}
const currentVersion = apiData.info?.version || "unknown";

// Lire l’ancienne version enregistrée
let savedVersion = null;
if (fs.existsSync(versionFilePath)) {
  savedVersion = fs.readFileSync(versionFilePath, "utf8").trim();
}

// Comparer
if (savedVersion !== currentVersion) {
  console.log(
    chalk.yellowBright(
      `⚠️  La version de l'API a changé (${savedVersion || "aucune"} → ${currentVersion}).`,
    ),
    chalk.gray(`(${process.env.OPENAPI_URL} - v${currentVersion})`),
  );
  console.log(
    chalk.cyan(
      "👉 Veuillez exécuter `pnpm run gen:api` pour régénérer les fichiers.",
    ),
  );
  // (optionnel) tu peux aussi sortir avec un code 1 pour bloquer la commande
  // process.exit(1);
} else {
  console.log(
    chalk.green("✅ Version API inchangée."),
    chalk.gray(`(${process.env.OPENAPI_URL} - v${currentVersion})`),
  );
}
