/**
 * User roles system
 * 
 * This module exports user roles constants and utilities for role checking.
 * Roles are defined in the backend: api/src/Entity/User.php
 */

// Export role constants and schemas
export { UserRole, userRoleSchema } from "@/schema/auth.schema";
export type { UserRoleType } from "@/schema/auth.schema";

// Export client-side role utilities
export {
  hasRole,
  isSuperAdmin,
  isAdmin,
  isUser,
  hasAnyRole,
  hasAllRoles,
  isAdminOrSuperAdmin,
} from "./user-roles";

// Server-side utilities are exported from user-session.server.ts
// Use them like:
// import { isUserAdmin, hasRole } from "@/auth/utils/user-session.server";
