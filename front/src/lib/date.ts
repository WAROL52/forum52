import { format, isValid, parseISO } from "date-fns";

export const formatDate = (dateString: string) => {
  try {
    const date = parseISO(dateString);
    if (!isValid(date)) {
      return dateString;
    }
    return format(date, "dd-MM-yyyy");
  } catch (error) {
    return dateString;
  }
};
