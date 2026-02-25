"use client";

import { DevTool } from "@hookform/devtools";
import { type FC, useEffect } from "react";
import {
  type EventType,
  type FieldErrors,
  type FieldPath,
  type FieldPathValue,
  type FieldValues,
  type FormState,
  type InternalFieldName,
  type Path,
  type ReadFormState,
  type UseFormReturn,
  useFormState,
} from "react-hook-form";
import { Alert, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Spinner } from "@/components/ui/spinner";
import { AppField } from "../fields/app-field";
import { AppFieldArray } from "../fields/app-field-array";
import { InfiniteSelectField } from "../fields/infinite-select-field";
import { InputField } from "../fields/input-field";
import { InputOtpField } from "../fields/input-otp-field";
import { SelectField } from "../fields/select-field";
import { TextareaField } from "../fields/textarea-field";

export type UseAppFieldsProps<
  TFieldValues extends FieldValues = FieldValues,
  TTransformedValues = TFieldValues
> = {
  form: UseFormReturn<TFieldValues, any, TTransformedValues>;
  onSubmit?: (data: TTransformedValues) => void;
  subscribe?: {
    name?: Path<TFieldValues> | readonly Path<TFieldValues>[] | undefined;
    formState?: Partial<ReadFormState>;
    callback: (
      data: Partial<FormState<TFieldValues>> & {
        values: TFieldValues;
        name?: InternalFieldName;
        type?: EventType;
      }
    ) => void;
    exact?: boolean;
  };
};

export function useAppField<
  TFieldValues extends FieldValues = FieldValues,
  TName extends FieldPath<TFieldValues> = FieldPath<TFieldValues>,
  TTransformedValues = TFieldValues
>(props: UseAppFieldsProps<TFieldValues, TTransformedValues>) {
  const { form, onSubmit = () => {}, subscribe: subscribePayload } = props;
  type AppFieldPropsWithoutControl<F extends FC<any>> = Omit<
    React.ComponentProps<F>,
    "control" | "name"
  > & {
    name: TName;
  };
  const { control, subscribe } = form;
  useEffect(() => {
    return subscribe({
      ...subscribePayload,
      callback(data) {
        subscribePayload?.callback(data);
      },
    });
  });
  return {
    AppForm: ({
      withDevTool,
      ...props
    }: React.ComponentProps<"form"> & { withDevTool?: boolean }) => (
      <form {...props} onSubmit={form.handleSubmit(onSubmit)}>
        {process.env.NODE_ENV === "development" && <AppError />}
        {props.children}
        {withDevTool && (
          <DevTool
            // @ts-expect-error
            control={control}
          />
        )}
      </form>
    ),
    Submit: (props: React.ComponentProps<typeof Button>) => (
      <Button
        {...props}
        type="submit"
        disabled={
          form.formState.isSubmitting ||
          form.formState.disabled ||
          props.disabled
        }
        onClick={(e) => {
          form.trigger();
          props.onClick?.(e);
        }}
      >
        {form.formState.isSubmitting && <Spinner />}
        {props.children}
      </Button>
    ),
    Reset: (props: React.ComponentProps<typeof Button>) => (
      <Button
        variant="secondary"
        {...props}
        onClick={(e) => {
          form.reset();
          props.onClick?.(e);
        }}
        disabled={
          form.formState.isSubmitting ||
          form.formState.disabled ||
          props.disabled
        }
        type="reset"
      />
    ),
    AppError,
    AppValue,
    DevTool: () => (
      <DevTool
        // @ts-expect-error
        control={control}
      />
    ),

    AppFieldArray: (
      props: AppFieldPropsWithoutControl<typeof AppFieldArray>
    ) => (
      // @ts-expect-error
      <AppFieldArray {...props} control={control} />
    ),
    AppField: (props: AppFieldPropsWithoutControl<typeof AppField>) => (
      // @ts-expect-error
      <AppField {...props} control={control} />
    ),
    Input: (props: AppFieldPropsWithoutControl<typeof InputField>) => (
      <InputField {...props} control={control} />
    ),
    Select: (props: AppFieldPropsWithoutControl<typeof SelectField>) => (
      <SelectField {...props} control={control} />
    ),
    InfiniteSelect: (props: AppFieldPropsWithoutControl<typeof InfiniteSelectField>) => (
      <InfiniteSelectField {...props} control={control} />
    ),
    InputOtp: (props: AppFieldPropsWithoutControl<typeof InputOtpField>) => (
      <InputOtpField {...props} control={control} />
    ),
    Textarea: (props: AppFieldPropsWithoutControl<typeof TextareaField>) => (
      <TextareaField {...props} control={control} />
    ),
  };

  function AppValue<N extends TName>({
    name,
    children,
  }: {
    name: N;
    children: (value: FieldPathValue<TFieldValues, N>) => React.ReactNode;
  }) {
    return (
      <AppField name={name} control={control}>
        {({ field }) => <>{children(field.value)}</>}
      </AppField>
    );
  }
  function AppError({
    children = (errors) =>
      Object.keys(errors).length > 0 &&
      process.env.NODE_ENV === "development" &&
      null,
      // <Alert variant={"destructive"} className="mb-3 overflow-auto">
      //   <AlertTitle>NODE_ENV === "{process.env.NODE_ENV}"</AlertTitle>
      //   <pre>{JSON.stringify(errors, null, 2)}</pre>
      // </Alert>
    ...props
  }: {
    children?: (errors: FieldErrors<TFieldValues>) => React.ReactNode;
    disabled?: boolean;
    name?:
      | FieldPath<TFieldValues>
      | FieldPath<TFieldValues>[]
      | readonly FieldPath<TFieldValues>[];
    exact?: boolean;
  }) {
    const { errors } = useFormState({ ...props, control });
    return <>{children(errors)}</>;
  }
}
