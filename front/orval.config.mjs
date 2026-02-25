import dotenv from "dotenv";
import { defineConfig } from "orval";

dotenv.config();

const outputPath = "./src/generated";

export default defineConfig({
  zod: {
    output: {
      client: "zod",
      mode: "tags",
      target: `${outputPath}/zod`,
      namingConvention: "kebab-case",
      fileExtension: ".schema.ts",
      override: {
        zod: {
          generateEachHttpStatus: true,
          generate: {
            response: true,
            query: true,
            param: true,
            header: true,
            body: true,
          },
          strict: {
            response: true,
            query: true,
            param: true,
            header: true,
            body: true,
          },
          coerce: {
            response: true,
            query: true,
            param: true,
            header: true,
            body: true,
          },
        },
      },
    },
    input: {
      target: process.env.OPENAPI_URL,
    },
  },
  fetch: {
    output: {
      override: {
        mutator: {
          path: "./src/lib/fetch-api.ts",
          name: "fetchApi",
        },
      },
      mode: "tags-split",
      // headers: true,
      target: `${outputPath}/api`,
      schemas: `${outputPath}/types`,
      namingConvention: "kebab-case",
      // fileExtension: ".api.ts",
      client: "fetch",
      // baseUrl: "http://localhost:8000",
      // baseUrl: {
      //   // getBaseUrlFromSpecification: true,
      //   // index: process.env.NODE_ENV === "production" ? 1 : 0,
      //   // baseUrl:()=> process.env.SERVER_URL,
      // },
      mock: false,
    },
    input: {
      target: process.env.OPENAPI_URL,
    },
  },
});
