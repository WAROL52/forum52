import { onError } from "@orpc/client";
import { experimental_SmartCoercionPlugin as SmartCoercionPlugin } from "@orpc/json-schema";
import type { SchemaConvertOptions } from "@orpc/openapi";
import { OpenAPIHandler } from "@orpc/openapi/fetch";
import { OpenAPIReferencePlugin } from "@orpc/openapi/plugins";
import { ZodToJsonSchemaConverter } from "@orpc/zod/zod4";
import type z from "zod";
import { router } from "@/routers";
import * as appSchema from "@/schema";

const commonSchemas: Record<
  string,
  {
    strategy?: SchemaConvertOptions["strategy"];
    schema: z.ZodType;
  }
> = Object.fromEntries(
  Object.entries(appSchema)
    .filter(
      ([, schema]) => schema && typeof schema === "object" && "_def" in schema,
    )
    .map(([key, schema]) => [key, { schema: schema as z.ZodType }]),
);

export const openApiHandler = new OpenAPIHandler(router, {
  interceptors: [
    onError((error) => {
      console.error(error);
    }),
  ],
  plugins: [
    new SmartCoercionPlugin({
      schemaConverters: [new ZodToJsonSchemaConverter()],
    }),
    new OpenAPIReferencePlugin({
      schemaConverters: [new ZodToJsonSchemaConverter()],
      specGenerateOptions: {
        info: {
          title: "ORPC Playground",
          version: "1.0.0",
        },

        commonSchemas,
        security: [{ bearerAuth: [] }],
        components: {
          securitySchemes: {
            bearerAuth: {
              type: "http",
              scheme: "bearer",
            },
          },
        },
      },
      docsConfig: {
        authentication: {
          securitySchemes: {
            bearerAuth: {
              token: "default-token",
            },
          },
        },
      },
    }),
  ],
});
