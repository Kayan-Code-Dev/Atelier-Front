<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Domain\Tools;

enum CustomerToolName: string
{
    case GetCustomer = 'GetCustomer';
    case SearchCustomer = 'SearchCustomer';
    case CreateCustomer = 'CreateCustomer';
    case UpdateCustomer = 'UpdateCustomer';
    case GetCustomerHistory = 'GetCustomerHistory';
    case GetCustomerMeasurements = 'GetCustomerMeasurements';
    case GetCustomerReservations = 'GetCustomerReservations';
    case GetCustomerInvoices = 'GetCustomerInvoices';
    case GetCustomerOrders = 'GetCustomerOrders';
    case GetCustomerNotes = 'GetCustomerNotes';
    case GetCustomerTimeline = 'GetCustomerTimeline';
    case CustomerExists = 'CustomerExists';
    case MergeCustomers = 'MergeCustomers';
    case CustomerSummary = 'CustomerSummary';
    case CustomerInsights = 'CustomerInsights';
}
