export type TCreateCategoryRequest = {
  name: string;
  description: string;
};

export type TCategory = {
  id: number;
  name: string;
  description: string;

  created_at: string;
  updated_at: string;
  deleted_at: string;
};

export type TCreateCategoryResponse = TCategory;

export type TUpdateCategoryRequest = Partial<TCreateCategoryRequest>;
