FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

COPY *.sln ./
COPY SmsPlatform.Domain/*.csproj ./SmsPlatform.Domain/
COPY SmsPlatform.Application/*.csproj ./SmsPlatform.Application/
COPY SmsPlatform.Infrastructure/*.csproj ./SmsPlatform.Infrastructure/
COPY SmsPlatform.Api/*.csproj ./SmsPlatform.Api/

RUN dotnet restore

COPY . .
RUN dotnet publish SmsPlatform.Api/SmsPlatform.Api.csproj -c Release -o /app/publish

FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS runtime
WORKDIR /app
COPY --from=build /app/publish .

ENV ASPNETCORE_URLS=http://+:5000
EXPOSE 5000

ENTRYPOINT ["dotnet", "SmsPlatform.Api.dll"]
