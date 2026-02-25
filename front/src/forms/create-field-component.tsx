import type { FC, ReactNode } from "react";
import type { FieldPath, FieldValues } from "react-hook-form";
import type { AppFieldProps } from "./fields/app-field";

export type MinimalFieldProps = {
  label?: ReactNode;
  description?: ReactNode;
  placeholder?: string;
};
export type CompleteFieldProps<
  P extends object,
  TFieldValues extends FieldValues = FieldValues,
  TName extends FieldPath<TFieldValues> = FieldPath<TFieldValues>,
  TTransformedValues = TFieldValues,
> = P &
  MinimalFieldProps &
  Omit<AppFieldProps<TFieldValues, TName, TTransformedValues>, "children">;

type FieldComponentProps<
  P extends object,
  TFieldValues extends FieldValues = FieldValues,
  TName extends FieldPath<TFieldValues> = FieldPath<TFieldValues>,
  TTransformedValues = TFieldValues,
> = MinimalFieldProps &
  Omit<AppFieldProps<TFieldValues, TName, TTransformedValues>, "children"> & {
    inputProps: P;
  };
export function createFieldComponent<P extends object>(
  FieldComponent: FC<FieldComponentProps<P>>,
) {
  return function FieldWrapper<
    TFieldValues extends FieldValues = FieldValues,
    TName extends FieldPath<TFieldValues> = FieldPath<TFieldValues>,
    TTransformedValues = TFieldValues,
  >(props: CompleteFieldProps<P, TFieldValues, TName, TTransformedValues>) {
    const {
      name,
      defaultValue,
      label,
      control,
      description,
      disabled,
      placeholder,
      ...rest
    } = props;
    return (
      <FieldComponent
        control={control as FieldComponentProps<P>["control"]}
        inputProps={rest as P}
        label={label}
        description={description}
        placeholder={placeholder}
        name={name}
        defaultValue={defaultValue}
        disabled={disabled}
      />
    );
  };
}
