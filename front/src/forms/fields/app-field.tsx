"use client";
import {
  type Control,
  Controller,
  type ControllerProps,
  type FieldPath,
  type FieldPathValue,
  type FieldValues,
} from "react-hook-form";

export type AppFieldProps<
  TFieldValues extends FieldValues = FieldValues,
  TName extends FieldPath<TFieldValues> = FieldPath<TFieldValues>,
  TTransformedValues = TFieldValues,
> = {
  control: Control<TFieldValues, any, TTransformedValues>;
  defaultValue?: FieldPathValue<TFieldValues, TName>;
  name: TName;
  disabled?: boolean;
  children: ControllerProps<TFieldValues, TName, TTransformedValues>["render"];
};

export function AppField<
  TFieldValues extends FieldValues = FieldValues,
  TName extends FieldPath<TFieldValues> = FieldPath<TFieldValues>,
  TTransformedValues = TFieldValues,
>(props: AppFieldProps<TFieldValues, TName, TTransformedValues>) {
  const { children, ...rest } = props;
  return <Controller {...rest} render={children} />;
}
