import { ORPCError, os } from "@orpc/server";
import type z from "zod";
import { getUserSession } from "@/auth/utils/user-session.server";

import type { UserSession } from "@/schema/auth.schema";
import type { ErrorResponse, ValidationErrorResponseResponse } from "@/types";

export type Context = {
  userSession: UserSession | null;
};

export const errorStandards = os.errors({
  BAD_REQUEST: {
    message: "Mauvaise requête",
    status: 400,
  },
  UNAUTHORIZED: {
    message: "Non autorisé",
    status: 401,
  },
  FORBIDDEN: {
    message: "Interdit",
    status: 403,
  },
  NOT_FOUND: {
    message: "Non trouvé",
    status: 404,
  },
  INTERNAL_SERVER_ERROR: {
    message: "Erreur interne du serveur",
    status: 500,
  },
});

function errorCode(status: number) {
  switch (status) {
    case 400:
      return "BAD_REQUEST";
    case 401:
      return "UNAUTHORIZED";
    case 404:
      return "NOT_FOUND";
    case 500:
      return "INTERNAL_SERVER_ERROR";
    default:
      return "UNKNOWN_ERROR";
  }
}

export function safeResponse<
  RES extends {
    data: ValidationErrorResponseResponse | {};
    status: number;
  },
  Z_SCHEMA extends z.ZodObject<any>,
>(result: RES, schema: Z_SCHEMA): z.infer<Z_SCHEMA> {
  if (result.status !== 200 && result.status !== 201) {
    throw new ORPCError(errorCode(result.status), {
      message:
        "error" in result.data
          ? JSON.stringify(result.data.error)
          : "Une erreur est survenue",
      status: result.status,
      data: result.data as ErrorResponse,
    });
  }
  const zData = schema.safeParse(result.data);
  if (!zData.success) {
    console.log({ result: JSON.stringify(result.data, null, 2) });
    console.log(zData.error);

    throw new ORPCError("BAD_REQUEST", {
      message: "Réponse serveur invalide",
      status: 500,
    });
  }
  return zData.data;
}

export function safeSchema<I extends z.ZodTypeAny, O extends z.ZodTypeAny>(
  input: I,
  output: O,
) {
  return { input, output };
}

export const publicProcedure = errorStandards
  .$context<Context>()
  .use(async ({ context, next }) => {
    // Public procedure, no authentication required
    return next({
      context: {
        userSession: await getUserSession(),
      },
    });
  });
