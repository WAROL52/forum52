"use client";
import {
  type Control,
  Controller,
  type ControllerFieldState,
  type ControllerRenderProps,
  type FieldArrayPath,
  type FieldPath,
  type FieldValues,
  type UseFieldArrayReturn,
  type UseFormStateReturn,
  useFieldArray,
} from "react-hook-form";

export type AppFieldArrayProps<
  TFieldValues extends FieldValues = FieldValues,
  TFieldArrayName extends
    FieldArrayPath<TFieldValues> = FieldArrayPath<TFieldValues>,
  TKeyName extends string = "id",
> = {
  control: Control<TFieldValues, any, TFieldValues>;
  name: TFieldArrayName;
  disabled?: boolean;
  keyName?: TKeyName;
  children: (
    controller: {
      field: ControllerRenderProps<TFieldValues, FieldPath<TFieldValues>>;
      fieldState: ControllerFieldState;
      formState: UseFormStateReturn<TFieldValues>;
    },
    handler: UseFieldArrayReturn<TFieldValues, TFieldArrayName, TKeyName>,
  ) => React.ReactNode;
};

export function AppFieldArray<
  TFieldValues extends FieldValues = FieldValues,
  TFieldArrayName extends
    FieldArrayPath<TFieldValues> = FieldArrayPath<TFieldValues>,
  TKeyName extends string = "id",
>(props: AppFieldArrayProps<TFieldValues, TFieldArrayName, TKeyName>) {
  const { children, keyName, ...rest } = props;
  const handler = useFieldArray({
    control: rest.control,
    name: rest.name,
    keyName,
  });
  return (
    <Controller
      {...rest}
      name={rest.name as FieldPath<TFieldValues>}
      render={(ctrl) => {
        return <>{children(ctrl, handler)}</>;
      }}
    />
  );
}
