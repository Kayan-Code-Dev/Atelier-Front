import { TCategory } from "../category/category.type";

export type TCreateSubcategoryRequest = {
  name: string;
  description: string;
  category_id: number;
};

export type TSubcategory = {
  id: number;
  name: string;
  description: string;

  category_id: number;
  created_at: string;
  updated_at: string;
  deleted_at: string;
  category: TCategory;
};

export type TCreateSubcategoryResponse = TSubcategory;

export type TUpdateSubcategoryRequest = Partial<TCreateSubcategoryRequest>;
