import "server-only";
import {
  getFreshUserSession,
  setUserSession,
} from "@/auth/utils/user-session.server";
import type { UserSession } from "@/schema";
import { publicProcedure } from "./public-procedure";

export const protectedProcedure = publicProcedure
  .errors({
    UNAUTHORIZED: {
      status: 401,
      message: "User is not authorized",
    },
  })
  .use(async ({ context, next, errors }) => {
    if (!context.userSession) {
      throw errors.UNAUTHORIZED();
    }
    if (!isFresh(context.userSession)) {
      const freshUserSession = await getFreshUserSession(
        context.userSession.session.token,
      );
      // await setUserSession(freshUserSession);
      if (!freshUserSession) {
        throw errors.UNAUTHORIZED();
      }
      return next({
        context: {
          ...context,
          userSession: freshUserSession,
        },
      });
    }
    return next({
      context: {
        ...context,
        userSession: context.userSession,
      },
    });
  });

function isFresh(userSession: UserSession): boolean {
  const freshAgeInMilliseconds = userSession.session.freshAgeInSeconds * 1000;
  const updatedAt = userSession.session.updatedAt.getTime();
  const now = Date.now();
  return now - updatedAt <= freshAgeInMilliseconds;
}
