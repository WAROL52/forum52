import { isUserAdmin } from "@/auth/utils/user-session.server";
import { protectedProcedure } from "./protected-procedure";

export const adminProcedure = protectedProcedure
  .errors({
    FORBIDDEN: {
      status: 403,
      message: "User is not an admin",
    },
  })
  .use(async ({ context, next, errors }) => {
    if (!isUserAdmin(context.userSession)) {
      throw errors.FORBIDDEN();
    }
    return next({
      context: {
        ...context,
        userSession: context.userSession,
      },
    });
  });
