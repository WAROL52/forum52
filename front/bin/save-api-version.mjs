#!/usr/bin/env node

import dotenv from "dotenv";
import fs from "fs";
import path from "path";

dotenv.config();

if (!process.env.OPENAPI_BACKUP_FILE) {
  console.error(
    "❌ La variable d'environnement OPENAPI_BACKUP_FILE n'est pas définie.",
  );
  process.exit(0);
}

if (!process.env.OPENAPI_URL) {
  console.error(
    "❌ La variable d'environnement OPENAPI_URL n'est pas définie.",
  );
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

let apiData;
if (isUrl) {
  // Fetch from URL
  try {
    const response = await fetch(apiJsonPath);
    if (!response.ok) {
      console.error(
        `❌ Erreur lors de la récupération de l'API depuis l'URL : ${response.statusText}`,
      );
      process.exit(0);
    }
    apiData = await response.json();
  } catch (error) {
    console.error(
      `❌ Erreur lors de la récupération de l'API : ${error.message}`,
    );
    process.exit(0);
  }
} else {
  // Read from file path
  if (!fs.existsSync(apiJsonPath)) {
    console.error(`❌ Le fichier ${apiJsonPath} n'existe pas.`);
    process.exit(0);
  }
  apiData = JSON.parse(fs.readFileSync(apiJsonPath, "utf8"));
}

const currentVersion = apiData.info?.version || "unknown";

// Enregistre la version actuelle
fs.writeFileSync(versionFilePath, currentVersion, "utf8");
console.log(`✅ Version API enregistrée : ${currentVersion}`);
